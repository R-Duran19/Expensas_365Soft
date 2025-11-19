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
                      $subQ->where('nombre_completo', 'like', "%{$search}%")
                           ->orWhere('ci', 'like', "%{$search}%");
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
        // Reglas de validación base
        $rules = [
            'propietario_id' => 'required|exists:propietarios,id',
            'propiedad_id' => 'required|exists:propiedades,id',
            'payment_type_id' => 'required|exists:payment_types,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000'
        ];

        // Reglas adicionales para pagos QR
        if ($request->qr_paid || $request->qr_id) {
            $rules = array_merge($rules, [
                'qr_id' => 'required|exists:qr,id',
                'qr_alias' => 'required|string|max:100',
                'qr_detalle_glosa' => 'nullable|string|max:255',
                'qr_fecha_generacion' => 'nullable|date'
            ]);
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            // Validación específica para pagos QR
            $qrInfo = null;
            $qrMonto = null;

            if ($request->qr_paid && $request->qr_id) {
                // Verificar que el QR exista y esté pagado
                $qrInfo = \App\Models\Qr::find($request->qr_id);

                if (!$qrInfo) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'QR no encontrado en el sistema'
                    ], 400);
                }

                if ($qrInfo->estado !== \App\Models\Qr::ESTADO_PAGADO) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'El QR no ha sido pagado aún. Estado actual: ' . $qrInfo->estado
                    ], 400);
                }

                // Validar consistencia de montos
                $qrMonto = $qrInfo->monto;
                $formMonto = (float) $request->amount;

                if (abs($qrMonto - $formMonto) > 0.01) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Inconsistencia de montos. Monto QR: Bs {$qrMonto}, Monto formulario: Bs {$formMonto}"
                    ], 400);
                }

                // Validar que el QR pertenezca al mismo propietario
                if ($qrInfo->propietario_id !== $request->propietario_id) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'El QR no pertenece al propietario seleccionado'
                    ], 400);
                }
            }

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

            // Crear pago asociado al período activo con datos del QR si aplica
            $paymentData = [
                'receipt_number' => $receiptNumber,
                'propiedad_id' => $request->propiedad_id,
                'propietario_id' => $request->propietario_id,
                'payment_type_id' => $request->payment_type_id,
                'expense_period_id' => $activePeriod->id,
                'amount' => $qrMonto ?: $request->amount, // Usar monto del QR si existe
                'payment_date' => $request->payment_date,
                'registered_at' => now(),
                'reference' => $request->reference,
                'notes' => $request->notes,
                'status' => 'active'
            ];

            // Agregar metadatos del QR si es un pago QR
            if ($qrInfo) {
                $paymentData['notes'] .= "\n\n--- DATOS DEL QR ---\n";
                $paymentData['notes'] .= "QR ID: {$qrInfo->id}\n";
                $paymentData['notes'] .= "Alias: {$qrInfo->alias}\n";
                $paymentData['notes'] .= "Monto QR: Bs {$qrInfo->monto}\n";
                $paymentData['notes'] .= "Detalle: {$qrInfo->detalle_glosa}\n";
                if ($qrInfo->expensa_id) {
                    $paymentData['notes'] .= "ID Expensa Asociada: {$qrInfo->expensa_id}\n";
                }
                $paymentData['notes'] .= "Fecha Generación: " . now()->format('d/m/Y H:i:s');
            }

            $payment = Payment::create($paymentData);

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

            // Validación de consistencia para pagos QR
            if ($qrInfo) {
                // Verificar que la imputación coincida con el monto del QR
                $allocatedAmount = $allocationResult['allocated_amount'] ?? 0;
                if (abs($allocatedAmount - $qrMonto) > 0.01) {
                    Log::warning("Inconsistencia en imputación QR: Monto QR={$qrMonto}, Monto Imputado={$allocatedAmount}", [
                        'qr_id' => $qrInfo->id,
                        'payment_id' => $payment->id,
                        'receipt_number' => $receiptNumber
                    ]);
                }
            }

            DB::commit();

            // Mensaje específico para pagos QR
            $successMessage = $qrInfo
                ? "Pago QR registrado exitosamente. Recibo: {$receiptNumber} - Monto: Bs {$qrMonto}"
                : 'Pago registrado e imputado exitosamente';

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'payment_id' => $payment->id,
                'receipt_number' => $receiptNumber,
                'allocation_result' => $allocationResult,
                'expense' => $expense,
                'qr_info' => $qrInfo ? [
                    'id' => $qrInfo->id,
                    'alias' => $qrInfo->alias,
                    'monto' => $qrInfo->monto,
                    'estado' => $qrInfo->estado
                ] : null
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
    public function getOwnersWithExpenses($periodId, Request $request): JsonResponse
    {
        try {
            $search = $request->get('search', '');
            $page = $request->get('page', 1);
            $perPage = 15;

            // Obtener TODAS las expensas con deuda en el período (sin paginar)
            $query = PropertyExpense::with(['propiedad', 'propiedad.propietarios'])
                ->where('expense_period_id', $periodId)
                ->where('balance', '>', 0);

            // Búsqueda global
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('propiedad', function ($prop) use ($search) {
                        $prop->where('codigo', 'like', "%{$search}%")
                             ->orWhere('ubicacion', 'like', "%{$search}%");
                    })
                    ->orWhereHas('propiedad.propietarios', function ($prop) use ($search) {
                        $prop->where('propietario_propiedad.es_propietario_principal', true)
                             ->whereNull('propietario_propiedad.fecha_fin')
                             ->where('propietarios.nombre_completo', 'like', "%{$search}%")
                             ->orWhere('propietarios.ci', 'like', "%{$search}%");
                    });
                });
            }

            // Obtener TODAS las expensas que coinciden con la búsqueda
            $allExpenses = $query->orderBy('created_at', 'desc')->get();

            // Agrupar por propietario principal
            $ownersData = [];
            foreach ($allExpenses as $expense) {
                // Obtener el propietario principal de la propiedad
                $propietarioPrincipal = $expense->propiedad->propietarioPrincipal();

                if (!$propietarioPrincipal || !$propietarioPrincipal->activo) {
                    continue; // Skip si no hay propietario principal o no está activo
                }

                $ownerId = $propietarioPrincipal->id;

                if (!isset($ownersData[$ownerId])) {
                    $ownersData[$ownerId] = [
                        'id' => $propietarioPrincipal->id,
                        'nombre_completo' => $propietarioPrincipal->nombre_completo,
                        'ci' => $propietarioPrincipal->ci,
                        'telefono' => $propietarioPrincipal->telefono,
                        'expenses_count' => 0,
                        'total_debt' => 0,
                        'propiedades_count' => 0,
                        'expenses' => [],
                        'propiedades' => []
                    ];
                }

                // Acumular datos
                $ownersData[$ownerId]['expenses_count']++;
                $ownersData[$ownerId]['total_debt'] += $expense->balance;

                // Evitar duplicar propiedades
                $propertyExists = collect($ownersData[$ownerId]['propiedades'])
                    ->contains('id', $expense->propiedad->id);

                if (!$propertyExists) {
                    $ownersData[$ownerId]['propiedades_count']++;
                    $ownersData[$ownerId]['propiedades'][] = [
                        'id' => $expense->propiedad->id,
                        'codigo' => $expense->propiedad->codigo,
                        'ubicacion' => $expense->propiedad->ubicacion
                    ];
                }

                // Agregar expensa
                $ownersData[$ownerId]['expenses'][] = [
                    'id' => $expense->id,
                    'propiedad' => [
                        'id' => $expense->propiedad->id,
                        'codigo' => $expense->propiedad->codigo,
                        'ubicacion' => $expense->propiedad->ubicacion
                    ],
                    'balance' => $expense->balance,
                    'status' => $expense->status,
                    'total_amount' => $expense->total_amount
                ];
            }

            // Ordenar propietarios alfabéticamente
            $owners = collect(array_values($ownersData))->sortBy('nombre_completo')->values();

            // Ahora aplicamos paginación a los propietarios agrupados
            $totalOwners = $owners->count();
            $totalPages = ceil($totalOwners / $perPage);
            $currentPage = min($page, max(1, $totalPages)); // Validar página
            $offset = ($currentPage - 1) * $perPage;

            $paginatedOwners = $owners->slice($offset, $perPage)->values();

            // Log para debugging
            Log::info("Paginación de propietarios - Total expensas: {$allExpenses->count()}, Total propietarios únicos: {$totalOwners}, Páginas: {$totalPages}");

            return response()->json([
                'success' => true,
                'owners' => $paginatedOwners,
                'pagination' => [
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages,
                    'per_page' => $perPage,
                    'total' => $totalOwners,
                    'has_more' => $currentPage < $totalPages
                ]
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