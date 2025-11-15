<?php

namespace App\Http\Controllers;

use App\Models\PropertyExpense;
use App\Models\ExpensePeriod;
use App\Models\Propiedad;
use App\Models\Propietario;
use App\Services\ExpenseCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PropertyExpenseController extends \Illuminate\Routing\Controller
{
    protected ExpenseCalculatorService $calculator;

    public function __construct(ExpenseCalculatorService $calculator)
    {
        $this->calculator = $calculator;
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Mostrar vista principal de expensas con selector de períodos
     */
    public function index(Request $request): Response
    {
        // Si no hay período seleccionado, mostrar vista con selector
        if (!$request->has('period_id')) {
            $periods = ExpensePeriod::withCount('propertyExpenses')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            return Inertia::render('PropertyExpenses/PeriodSelector', [
                'periods' => $periods->map(function ($period) {
                    return [
                        'id' => $period->id,
                        'name' => $period->getPeriodName(),
                        'status' => $period->status,
                        'total_generated' => $period->total_generated,
                        'properties_count' => $period->property_expenses_count,
                        'can_generate' => $period->status === 'open',
                    ];
                }),
            ]);
        }

        // Si hay período seleccionado, mostrar expensas de ese período
        $request->validate([
            'period_id' => 'required|exists:expense_periods,id',
            'status' => 'nullable|in:pending,partial,paid,overdue,cancelled',
            'search' => 'nullable|string|max:100',
        ]);

        $period = ExpensePeriod::findOrFail($request->period_id);

        $query = PropertyExpense::with(['propiedad', 'propietario', 'inquilino'])
            ->where('expense_period_id', $request->period_id);

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('propiedad', function ($prop) use ($search) {
                    $prop->where('codigo', 'like', "%{$search}%")
                         ->orWhere('ubicacion', 'like', "%{$search}%");
                })->orWhereHas('propietario', function ($prop) use ($search) {
                    $prop->where('nombre_completo', 'like', "%{$search}%");
                });
            });
        }

        $expenses = $query->orderBy('created_at', 'desc')
            ->paginate(50)
            ->through(function ($expense) {
                return [
                    'id' => $expense->id,
                    'propiedad_codigo' => $expense->propiedad->codigo,
                    'propiedad_ubicacion' => $expense->propiedad->ubicacion,
                    'propietario_nombre' => $expense->propietario->nombre_completo ?? 'N/A',
                    'inquilino_nombre' => $expense->inquilino?->nombre_completo,
                    'facturar_a' => $expense->facturar_a,
                    'base_amount' => $expense->base_amount,
                    'water_amount' => $expense->water_amount,
                    'previous_debt' => $expense->previous_debt,
                    'total_amount' => $expense->total_amount,
                    'paid_amount' => $expense->paid_amount,
                    'balance' => $expense->balance,
                    'status' => $expense->status,
                    'due_date' => $expense->due_date,
                    'created_at' => $expense->created_at->format('d/m/Y H:i'),
                ];
            });

        // Estadísticas del período
        $stats = [
            'total_properties' => Propiedad::activas()->count(),
            'generated_expenses' => $period->propertyExpenses()->count(),
            'total_amount' => $period->propertyExpenses()->sum('total_amount'),
            'total_collected' => $period->propertyExpenses()->sum('paid_amount'),
            'pending_count' => $period->propertyExpenses()->pending()->count(),
            'paid_count' => $period->propertyExpenses()->paid()->count(),
            'partial_count' => $period->propertyExpenses()->partial()->count(),
        ];

        return Inertia::render('PropertyExpenses/Index', [
            'period' => [
                'id' => $period->id,
                'name' => $period->getPeriodName(),
                'status' => $period->status,
                'period_date' => $period->period_date,
            ],
            'expenses' => $expenses,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Mostrar formulario para generar expensas
     */
    public function create(Request $request): Response
    {
        $periods = ExpensePeriod::withCount('propertyExpenses')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return Inertia::render('PropertyExpenses/Generate', [
            'periods' => $periods->map(function ($period) {
                return [
                    'id' => $period->id,
                    'name' => $period->getPeriodName(),
                    'status' => $period->status,
                    'total_generated' => $period->total_generated,
                    'properties_count' => $period->property_expenses_count,
                    'can_generate' => $period->status === 'open',
                ];
            }),
        ]);
    }

    /**
     * Generar expensas para todas las propiedades de un período
     */
    public function generateForPeriod(Request $request): JsonResponse
    {
        $request->validate([
            'period_id' => 'required|exists:expense_periods,id',
            'factor_departamento' => 'required|numeric|min:0',
            'factor_comercial' => 'required|numeric|min:0',
        ]);

        $period = ExpensePeriod::findOrFail($request->period_id);

        if ($period->status !== 'open') {
            return response()->json([
                'message' => 'No se pueden generar expensas para un período cerrado',
                'status' => 'error'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $results = $this->calculator->generateForPeriod($period, [
                'factor_departamento' => $request->factor_departamento,
                'factor_comercial' => $request->factor_comercial,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Expensas generadas correctamente',
                'status' => 'success',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Error al generar expensas: ' . $e->getMessage());            
        }
    }


    /**
     * Mostrar detalle de una expensa específica
     */
    public function show(PropertyExpense $propertyExpense): Response
    {
        $propertyExpense->load([
            'expensePeriod',
            'propiedad.tipoPropiedad',
            'propietario',
            'inquilino',
            'paymentAllocations.payment',
            'details.propiedad'
        ]);

        // Obtener lecturas de agua si aplica
        $waterReadings = [];
        if ($propertyExpense->propiedad->medidor) {
            $lecturas = $propertyExpense->propiedad->medidor->lecturas()
                ->orderBy('fecha_lectura', 'desc')
                ->limit(2)
                ->get();

            $waterReadings = $lecturas->map(function ($lectura) {
                return [
                    'fecha' => $lectura->fecha_lectura->format('d/m/Y'),
                    'lectura' => $lectura->lectura_actual,
                    'consumo' => $lectura->consumo,
                    'periodo' => $lectura->periodo_formateado,
                ];
            });
        }

        return Inertia::render('PropertyExpenses/Show', [
            'expense' => [
                'id' => $propertyExpense->id,
                'period' => $propertyExpense->expensePeriod->getPeriodName(),
                'propiedad' => [
                    'codigo' => $propertyExpense->propiedad->codigo,
                    'ubicacion' => $propertyExpense->propiedad->ubicacion,
                    'metros_cuadrados' => $propertyExpense->propiedad->metros_cuadrados,
                    'tipo_propiedad' => $propertyExpense->propiedad->tipoPropiedad->nombre,
                ],
                'propietario' => $propertyExpense->propietario?->nombre_completo,
                'inquilino' => $propertyExpense->inquilino?->nombre_completo,
                'facturar_a' => $propertyExpense->facturar_a,
                'desglose' => [
                    'base_amount' => $propertyExpense->base_amount,
                    'water_amount' => $propertyExpense->water_amount,
                    'other_amount' => $propertyExpense->other_amount,
                    'previous_debt' => $propertyExpense->previous_debt,
                    'total_amount' => $propertyExpense->total_amount,
                    'paid_amount' => $propertyExpense->paid_amount,
                    'balance' => $propertyExpense->balance,
                ],
                'agua' => [
                    'previous_reading' => $propertyExpense->water_previous_reading,
                    'current_reading' => $propertyExpense->water_current_reading,
                    'consumption' => $propertyExpense->water_consumption,
                    'factor' => $propertyExpense->water_factor,
                ],
                'status' => $propertyExpense->status,
                'due_date' => $propertyExpense->due_date,
                'paid_at' => $propertyExpense->paid_at,
                'notes' => $propertyExpense->notes,
                'created_at' => $propertyExpense->created_at->format('d/m/Y H:i'),
                'water_readings' => $waterReadings,
                'payment_allocations' => $propertyExpense->paymentAllocations->map(function ($allocation) {
                    return [
                        'id' => $allocation->id,
                        'amount' => $allocation->amount,
                        'payment' => [
                            'id' => $allocation->payment->id,
                            'payment_date' => $allocation->payment->payment_date->format('d/m/Y'),
                            'amount' => $allocation->payment->amount,
                            'payment_type' => $allocation->payment->payment_type->nombre,
                        ],
                        'created_at' => $allocation->created_at->format('d/m/Y H:i'),
                    ];
                }),
                'property_details' => $propertyExpense->details->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'propiedad' => [
                            'codigo' => $detail->propiedad_codigo,
                            'ubicacion' => $detail->propiedad_ubicacion,
                            'metros_cuadrados' => $detail->metros_cuadrados,
                            'tipo_propiedad' => $detail->tipo_propiedad,
                        ],
                        'factores' => [
                            'factor_expensas' => $detail->factor_expensas,
                            'factor_agua' => $detail->factor_agua,
                            'factor_calculado' => $detail->factor_calculado,
                        ],
                        'montos' => [
                            'base_amount' => $detail->base_amount,
                            'water_amount' => $detail->water_amount,
                            'total_amount' => $detail->total_amount,
                        ],
                        'agua' => [
                            'medidor_codigo' => $detail->water_medidor_codigo,
                            'consumption_m3' => $detail->water_consumption_m3,
                            'previous_reading' => $detail->water_previous_reading,
                            'current_reading' => $detail->water_current_reading,
                            'has_water_meter' => $detail->hasWaterMeter(),
                            'readings_summary' => $detail->water_readings_summary,
                        ],
                    ];
                }),
            ],
        ]);
    }

    /**
     * Actualizar una expensa (sí aún no está pagada)
     */
    public function update(Request $request, PropertyExpense $propertyExpense): JsonResponse
    {
        if ($propertyExpense->status === 'paid') {
            return response()->json([
                'message' => 'No se puede modificar una expensa ya pagada',
                'status' => 'error'
            ], 422);
        }

        $request->validate([
            'base_amount' => 'required|numeric|min:0',
            'water_amount' => 'required|numeric|min:0',
            'other_amount' => 'nullable|numeric|min:0',
            'previous_debt' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'due_date' => 'nullable|date|after:today',
        ]);

        try {
            DB::beginTransaction();

            $propertyExpense->update([
                'base_amount' => $request->base_amount,
                'water_amount' => $request->water_amount,
                'other_amount' => $request->other_amount ?? 0,
                'previous_debt' => $request->previous_debt,
                'total_amount' => $request->base_amount + $request->water_amount + ($request->other_amount ?? 0) + $request->previous_debt,
                'balance' => ($request->base_amount + $request->water_amount + ($request->other_amount ?? 0) + $request->previous_debt) - $propertyExpense->paid_amount,
                'notes' => $request->notes,
                'due_date' => $request->due_date,
            ]);

            $propertyExpense->updateStatus();

            DB::commit();

            return response()->json([
                'message' => 'Expensa actualizada correctamente',
                'status' => 'success',
                'expense' => $propertyExpense->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception('Error al actualizar expensa: ' . $e->getMessage());
        }
    }

    /**
     * Calcular el monto de una propiedad específica (preview)
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'propiedad_id' => 'required|exists:propiedades,id',
            'period_id' => 'required|exists:expense_periods,id',
            'factor_departamento' => 'required|numeric|min:0',
            'factor_comercial' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->calculator->calculateProperty(
                $request->propiedad_id,
                $request->period_id,
                [
                    'factor_departamento' => $request->factor_departamento,
                    'factor_comercial' => $request->factor_comercial,
                ]
            );

            return response()->json([
                'status' => 'success',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            throw new \Exception('Error al calcular expensa: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar una expensa (sí está pendiente)
     */
    public function destroy(PropertyExpense $propertyExpense): JsonResponse
    {
        if ($propertyExpense->status !== 'pending') {
            return response()->json([
                'message' => 'Solo se pueden eliminar expensas pendientes',
                'status' => 'error'
            ], 422);
        }

        try {
            $propertyExpense->delete();

            return response()->json([
                'message' => 'Expensa eliminada correctamente',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            throw new \Exception('Error al eliminar expensa: ' . $e->getMessage());
        }
    }
}