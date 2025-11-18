<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Propietario;
use App\Models\Propiedad;
use App\Models\PropertyExpense;
use App\Models\ExpensePeriod;
use App\Services\PaymentAllocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected $paymentAllocationService;

    public function __construct(PaymentAllocationService $paymentAllocationService)
    {
        $this->paymentAllocationService = $paymentAllocationService;
    }

    /**
     * Mostrar listado de pagos
     */
    public function index(Request $request)
    {
        $query = Payment::with([
            'propietario',
            'propiedad',
            'paymentType',
            'allocations.propertyExpense.expensePeriod'
        ])
        ->when($request->search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('propietario', function ($subQ) use ($search) {
                      $subQ->where('nombre', 'like', "%{$search}%")
                           ->orWhere('apellido', 'like', "%{$search}%");
                  })
                  ->orWhereHas('propiedad', function ($subQ) use ($search) {
                      $subQ->where('codigo', 'like', "%{$search}%");
                  });
            });
        })
        ->when($request->status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($request->payment_type_id, function ($query, $typeId) {
            $query->where('payment_type_id', $typeId);
        })
        ->when($request->propietario_id, function ($query, $ownerId) {
            $query->where('propietario_id', $ownerId);
        })
        ->when($request->date_from, function ($query, $dateFrom) {
            $query->whereDate('payment_date', '>=', $dateFrom);
        })
        ->when($request->date_to, function ($query, $dateTo) {
            $query->whereDate('payment_date', '<=', $dateTo);
        })
        ->orderBy('payment_date', 'desc')
        ->orderBy('created_at', 'desc');

        $payments = $query->paginate(15);

        $paymentTypes = PaymentType::where('is_active', true)->get();

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'paymentTypes' => $paymentTypes,
            'filters' => $request->only(['search', 'status', 'payment_type_id', 'propietario_id', 'date_from', 'date_to'])
        ]);
    }

    /**
     * Mostrar vista para seleccionar propietario
     */
    public function selectOwner()
    {
        $activePeriod = $this->getActivePeriod();

        return Inertia::render('Payments/SelectOwner', [
            'activePeriod' => $activePeriod
        ]);
    }

    /**
     * Mostrar formulario para crear nuevo pago
     */
    public function create($propietarioId = null)
    {
        $paymentTypes = PaymentType::where('is_active', true)->get();

        // Obtener el período activo
        $activePeriod = $this->getActivePeriod();

        $selectedOwner = null;
        $currentExpense = null;

        // Si se proporcionó un ID de propietario, cargar sus datos
        if ($propietarioId) {
            $selectedOwner = Propietario::where('activo', true)
                ->where('id', $propietarioId)
                ->first();

            if ($selectedOwner && $activePeriod) {
                // Buscar expensa del propietario en el período activo
                $currentExpense = PropertyExpense::where('propietario_id', $propietarioId)
                    ->where('expense_period_id', $activePeriod->id)
                    ->with(['propiedad', 'expensePeriod'])
                    ->first();
            }
        }

        return Inertia::render('Payments/Create', [
            'paymentTypes' => $paymentTypes,
            'propietarios' => Propietario::where('activo', true)
                ->with('propiedades')
                ->orderBy('nombre_completo')
                ->get(),
            'nextReceiptNumber' => Payment::generateReceiptNumber(),
            'activePeriod' => $activePeriod,
            'selectedOwner' => $selectedOwner,
            'currentExpense' => $currentExpense
        ]);
    }

    /**
     * Guardar nuevo pago y procesar imputación
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'propietario_id' => 'required|exists:propietarios,id',
            'propiedad_id' => 'required|exists:propiedades,id',
            'payment_type_id' => 'required|exists:payment_types,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            // Obtener período activo
            $activePeriod = $this->getActivePeriod();

            if (!$activePeriod) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un período activo para registrar pagos'
                ], 400);
            }

            // Verificar que el propietario tenga expensa en el período activo
            $expense = PropertyExpense::where('propietario_id', $request->propietario_id)
                ->where('expense_period_id', $activePeriod->id)
                ->first();

            if (!$expense) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'El propietario no tiene una expensa generada para el período actual'
                ], 400);
            }

            // Generar número de recibo único
            $receiptNumber = Payment::generateReceiptNumber();

            // Crear pago asociado al período activo
            $payment = Payment::create([
                'receipt_number' => $receiptNumber,
                'propiedad_id' => $request->propiedad_id,
                'propietario_id' => $request->propietario_id,
                'payment_type_id' => $request->payment_type_id,
                'expense_period_id' => $activePeriod->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'registered_at' => now(),
                'reference' => $request->reference,
                'notes' => $request->notes,
                'status' => 'active'
            ]);

            // Procesar imputación automática (limitada al período activo)
            $allocationResult = $this->paymentAllocationService->allocatePaymentToPeriod(
                $payment,
                $activePeriod->id
            );

            if (!$allocationResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pago creado pero error en imputación: ' . $allocationResult['message'],
                    'payment_id' => $payment->id,
                    'receipt_number' => $receiptNumber
                ], 400);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado e imputado exitosamente',
                'payment_id' => $payment->id,
                'receipt_number' => $receiptNumber,
                'allocation_result' => $allocationResult,
                'expense' => $expense
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creando pago: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creando pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un pago específico
     */
    public function show(Payment $payment)
    {
        $payment->load([
            'propietario',
            'propiedad',
            'paymentType',
            'allocations.propertyExpense.expensePeriod',
            'allocations.propertyExpense.propiedad',
            'cancelledBy'
        ]);

        return Inertia::render('Payments/Show', [
            'payment' => $payment
        ]);
    }

    /**
     * Obtener deudas del propietario para el formulario
     */
    public function getOwnerDebts(Request $request): JsonResponse
    {
        $request->validate([
            'propietario_id' => 'required|exists:propietarios,id'
        ]);

        $debtSummary = $this->paymentAllocationService->getOwnerDebtSummary($request->propietario_id);

        return response()->json($debtSummary);
    }

    /**
     * Previsualizar imputación de pago
     */
    public function previewAllocation(Request $request): JsonResponse
    {
        $request->validate([
            'propietario_id' => 'required|exists:propietarios,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $preview = $this->paymentAllocationService->previewAllocation(
            $request->propietario_id,
            $request->amount
        );

        return response()->json($preview);
    }

    /**
     * Obtener propiedades de un propietario
     */
    public function getOwnerProperties(Request $request): JsonResponse
    {
        $request->validate([
            'propietario_id' => 'required|exists:propietarios,id'
        ]);

        $properties = DB::table('propietario_propiedad')
            ->join('propiedades', 'propiedades.id', '=', 'propietario_propiedad.propiedad_id')
            ->where('propietario_propiedad.propietario_id', $request->propietario_id)
            ->whereNull('propietario_propiedad.fecha_fin')
            ->where('propiedades.activo', true)
            ->select('propiedades.id', 'propiedades.codigo', 'propiedades.ubicacion', 'propiedades.metros_cuadrados')
            ->get();

        return response()->json([
            'success' => true,
            'properties' => $properties
        ]);
    }

    /**
     * Anular un pago existente
     */
    public function cancel(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        try {
            if ($payment->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'El pago ya está anulado'
                ], 400);
            }

            // Revertir imputaciones
            $reversalResult = $this->paymentAllocationService->reversePaymentAllocation($payment);

            if (!$reversalResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error anulando pago: ' . $reversalResult['message']
                ], 500);
            }

            // Actualizar razón de cancelación si se proporcionó una
            if ($request->cancellation_reason) {
                $payment->update([
                    'cancellation_reason' => $request->cancellation_reason
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago anulado exitosamente',
                'reversal_result' => $reversalResult
            ]);

        } catch (\Exception $e) {
            Log::error("Error anulando pago {$payment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error anulando pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API para obtener lista de pagos (para integraciones futuras)
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = Payment::with(['propietario', 'propiedad', 'paymentType'])
            ->when($request->propietario_id, function ($query, $ownerId) {
                $query->where('propietario_id', $ownerId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('payment_date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('payment_date', '<=', $dateTo);
            })
            ->orderBy('payment_date', 'desc');

        $payments = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Estadísticas de pagos para dashboard
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $baseQuery = Payment::where('status', 'active');

            if ($request->date_from) {
                $baseQuery->whereDate('payment_date', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $baseQuery->whereDate('payment_date', '<=', $request->date_to);
            }

            $stats = [
                'total_payments' => $baseQuery->count(),
                'total_amount' => $baseQuery->sum('amount'),
                'average_amount' => $baseQuery->avg('amount'),
                'payments_by_type' => $baseQuery->join('payment_types', 'payments.payment_type_id', '=', 'payment_types.id')
                    ->groupBy('payment_types.name')
                    ->selectRaw('payment_types.name as type, COUNT(*) as count, SUM(payments.amount) as total')
                    ->get(),
                'monthly_totals' => $baseQuery->selectRaw('DATE_FORMAT(payment_date, "%Y-%m") as month, COUNT(*) as count, SUM(amount) as total')
                    ->groupBy('month')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error("Error obteniendo estadísticas de pagos: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estadísticas'
            ], 500);
        }
    }

    /**
     * Obtener el período activo para pagos
     */
    private function getActivePeriod()
    {
        // Buscar período con estado 'open' o el más reciente sin cerrar
        return ExpensePeriod::where(function ($query) {
                $query->where('status', 'open')
                      ->orWhereNull('status');
            })
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();
    }

    /**
     * Obtener expensa del propietario en el período activo
     */
    public function getOwnerCurrentExpense(Request $request): JsonResponse
    {
        $request->validate([
            'propietario_id' => 'required|exists:propietarios,id'
        ]);

        try {
            $activePeriod = $this->getActivePeriod();

            if (!$activePeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay un período activo para registrar pagos'
                ]);
            }

            // Buscar expensa del propietario en el período activo
            $expense = PropertyExpense::where('propietario_id', $request->propietario_id)
                ->where('expense_period_id', $activePeriod->id)
                ->with(['expensePeriod', 'propiedad'])
                ->first();

            if (!$expense) {
                return response()->json([
                    'success' => false,
                    'message' => 'El propietario no tiene una expensa generada para el período actual',
                    'active_period' => $activePeriod
                ]);
            }

            return response()->json([
                'success' => true,
                'expense' => $expense,
                'active_period' => $activePeriod
            ]);

        } catch (\Exception $e) {
            Log::error("Error obteniendo expensa actual: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo información de la expensa'
            ]);
        }
    }

    /**
     * Obtener propietarios con expensas generadas para un período específico
     */
    public function getOwnersWithExpenses($periodId): JsonResponse
    {
        try {
            // Obtener todos los propietarios que tienen expensas en el período
            $owners = DB::table('property_expenses as pe')
                ->join('propietarios as p', 'pe.propietario_id', '=', 'p.id')
                ->join('expense_periods as ep', 'pe.expense_period_id', '=', 'ep.id')
                ->leftJoin('propiedades as pr', 'pe.propiedad_id', '=', 'pr.id')
                ->where('pe.expense_period_id', $periodId)
                ->where('p.activo', true)
                ->select([
                    'p.id',
                    'p.nombre_completo',
                    'p.ci',
                    'p.telefono',
                    DB::raw('COUNT(DISTINCT pe.id) as expenses_count'),
                    DB::raw('SUM(pe.balance) as total_debt'),
                    DB::raw('COUNT(DISTINCT pr.id) as propiedades_count')
                ])
                ->groupBy('p.id', 'p.nombre_completo', 'p.ci', 'p.telefono')
                ->orderBy('p.nombre_completo')
                ->get();

            // Obtener las expensas detalladas para cada propietario
            $ownersWithExpenses = $owners->map(function ($owner) use ($periodId) {
                $expenses = PropertyExpense::where('propietario_id', $owner->id)
                    ->where('expense_period_id', $periodId)
                    ->with(['propiedad'])
                    ->get();

                return [
                    'id' => $owner->id,
                    'nombre_completo' => $owner->nombre_completo,
                    'ci' => $owner->ci,
                    'telefono' => $owner->telefono,
                    'expenses_count' => (int) $owner->expenses_count,
                    'total_debt' => (float) $owner->total_debt,
                    'propiedades_count' => (int) $owner->propiedades_count,
                    'expenses' => $expenses->toArray(),
                    'propiedades' => $expenses->map(function ($expense) {
                        return $expense->propiedad ? [
                            'id' => $expense->propiedad->id,
                            'codigo' => $expense->propiedad->codigo,
                            'ubicacion' => $expense->propiedad->ubicacion
                        ] : null;
                    })->filter()->toArray()
                ];
            });

            return response()->json([
                'success' => true,
                'owners' => $ownersWithExpenses,
                'total_owners' => $ownersWithExpenses->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error obteniendo propietarios con expensas: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo lista de propietarios'
            ]);
        }
    }
}