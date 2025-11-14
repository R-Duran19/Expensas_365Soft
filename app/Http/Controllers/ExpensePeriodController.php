<?php

namespace App\Http\Controllers;

use App\Models\ExpensePeriod;
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

        // Verificar que no exista un período con el mismo año y mes
        $exists = ExpensePeriod::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'error' => 'Ya existe un período para este mes y año.'
            ]);
        }

        $period = ExpensePeriod::create([
            'year' => $validated['year'],
            'month' => $validated['month'],
            'period_date' => $validated['period_date'],
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
            'total_generated' => 0,
            'total_collected' => 0,
        ]);

        return redirect()
            ->route('expense-periods.show', $period)
            ->with('success', 'Período creado exitosamente.');
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

        return back()->with('success', 'Período cerrado exitosamente.');
    }
}
