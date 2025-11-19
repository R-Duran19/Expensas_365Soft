<?php

namespace App\Http\Controllers;

use App\Models\ExpensePeriod;
use App\Models\Payment;
use App\Models\PropertyExpense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ExpensePeriodController extends Controller
{
    /**
     * Listar períodos (abiertos, cerrados)
     */
    public function index()
    {
        $periods = ExpensePeriod::withCount(['propertyExpenses', 'cashTransactions'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(15);

        return Inertia::render('ExpensePeriods/Index', [
            'periods' => $periods
        ]);
    }

    /**
     * Crear nuevo período (mes/año)
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'year' => 'required|integer|min:2020|max:2100',
        'month' => 'required|integer|min:1|max:12',
        'period_date' => 'required|date',
        'notes' => 'nullable|string|max:1000'
    ]);

    $exists = ExpensePeriod::where('year', $validated['year'])
        ->where('month', $validated['month'])
        ->exists();

    if ($exists) {
        return back()->withErrors([
            'error' => 'Ya existe un período para este mes y año.'
        ]);
    }

    ExpensePeriod::create([
        'year' => $validated['year'],
        'month' => $validated['month'],
        'period_date' => $validated['period_date'],
        'status' => 'open',
        'notes' => $validated['notes'] ?? null,
        'total_generated' => 0,
        'total_collected' => 0,
    ]);

    return back()->with('success', 'Periodo creado');
}


    /**
     * Ver detalle de un período
     */
    public function show(ExpensePeriod $expensePeriod)
    {
        $expensePeriod->load([
            'propertyExpenses.propiedad',
            'propertyExpenses.propietario',
            'cashTransactions'
        ]);

        // Calcular totales
        $totalGenerated = $expensePeriod->propertyExpenses()->sum('total_amount');
        $totalCollected = $expensePeriod->propertyExpenses()->sum('paid_amount');
        
        // Actualizar totales si han cambiado
        if ($expensePeriod->total_generated != $totalGenerated || 
            $expensePeriod->total_collected != $totalCollected) {
            $expensePeriod->update([
                'total_generated' => $totalGenerated,
                'total_collected' => $totalCollected,
            ]);
        }

        return Inertia::render('ExpensePeriods/Show', [
            'period' => $expensePeriod,
            'statistics' => [
                'total_properties' => $expensePeriod->propertyExpenses()->count(),
                'total_generated' => $totalGenerated,
                'total_collected' => $totalCollected,
                'total_pending' => $totalGenerated - $totalCollected,
                'total_transactions' => $expensePeriod->cashTransactions()->count(),
            ]
        ]);
    }

    /**
     * Cerrar período (ya no se puede modificar)
     */
    public function close(ExpensePeriod $expensePeriod)
    {
        if ($expensePeriod->isClosed()) {
            return back()->withErrors([
                'error' => 'Este período ya está cerrado.'
            ]);
        }

        DB::transaction(function () use ($expensePeriod) {
            // Actualizar totales antes de cerrar
            $totalGenerated = $expensePeriod->propertyExpenses()->sum('total_amount');
            $totalCollected = $expensePeriod->propertyExpenses()->sum('paid_amount');

            $expensePeriod->update([
                'status' => 'closed',
                'closed_at' => now(),
                'total_generated' => $totalGenerated,
                'total_collected' => $totalCollected,
            ]);
        });

        // Calcular siguiente período sugerido
            $nextPeriod = $this->calculateNextPeriod($expensePeriod);

        return back()->with([
            'success' => 'Período cerrado exitosamente.',
            'showCreateNextPeriod' => true,
            'nextPeriod' => $nextPeriod
        ]);
    }

    /**
     * Ver todos los recibos de un período
     */
    public function receipts(ExpensePeriod $expensePeriod)
    {
        // Obtener pagos con todas las relaciones necesarias
        $receipts = Payment::where('expense_period_id', $expensePeriod->id)
            ->where('status', 'active')
            ->with([
                'paymentType:id,name',
                'propietario:id,nombre_completo',
                'propiedad:id,codigo,ubicacion'
            ])
            ->orderBy('receipt_number', 'desc')
            ->get()
            ->map(function ($payment) {
                // Corregir datos si es necesario
                if (!$payment->receipt_number) {
                    $payment->update(['receipt_number' => Payment::generateReceiptNumber()]);
                    $payment->refresh();
                }

                return [
                    'id' => $payment->id,
                    'receipt_number' => $payment->receipt_number ?: 'SIN RECIBO',
                    'amount' => (float) $payment->amount,
                    'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : $payment->created_at->format('Y-m-d'),
                    'reference' => $payment->reference,
                    'notes' => $payment->notes,
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    'payment_type' => $payment->paymentType ? [
                        'id' => $payment->paymentType->id,
                        'name' => $payment->paymentType->name
                    ] : [
                        'id' => 0,
                        'name' => 'Tipo no especificado'
                    ],
                    'propietario' => $payment->propietario ? [
                        'id' => $payment->propietario->id,
                        'nombre_completo' => $payment->propietario->nombre_completo
                    ] : (object) ['id' => 0, 'nombre_completo' => 'No asignado'],
                    'propiedad' => $payment->propiedad ? [
                        'id' => $payment->propiedad->id,
                        'codigo' => $payment->propiedad->codigo,
                        'ubicacion' => $payment->propiedad->ubicacion
                    ] : (object) ['id' => 0, 'codigo' => 'N/A', 'ubicacion' => 'No asignada']
                ];
            });

        // Calcular totales
        $totalAmount = $receipts->sum('amount');
        $totalReceipts = $receipts->count();

        // Agrupar por tipo de pago para estadísticas
        $paymentTypes = $receipts->filter(function ($receipt) {
            return $receipt['payment_type'] && $receipt['payment_type']['name'];
        })->groupBy('payment_type.name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount')
            ];
        });

        return Inertia::render('ExpensePeriods/Receipts', [
            'period' => $expensePeriod,
            'receipts' => $receipts,
            'statistics' => [
                'total_receipts' => $totalReceipts,
                'total_amount' => $totalAmount,
                'payment_types' => $paymentTypes
            ]
        ]);
    }

    /**
     * Calcular el siguiente período sugerido
     */
    private function calculateNextPeriod(ExpensePeriod $period): array
    {
        $nextMonth = $period->month + 1;
        $nextYear = $period->year;

        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        return [
            'year' => $nextYear,
            'month' => $nextMonth,
            'period_date' => "{$nextYear}-" . str_pad($nextMonth, 2, '0', STR_PAD_LEFT) . "-01"
        ];
    }
}
