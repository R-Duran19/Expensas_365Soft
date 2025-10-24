<?php

namespace App\Http\Controllers;

use App\Models\Expensa;
use App\Models\PeriodoFacturacion;
use App\Services\CalculoExpensasService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpensaController extends Controller
{
    protected $calculoService;

    public function __construct(CalculoExpensasService $calculoService)
    {
        $this->calculoService = $calculoService;
    }

    public function index(Request $request)
    {
        $query = Expensa::with([
            'propiedad',
            'periodoFacturacion',
            'usuarioGeneracion'
        ]);

        // Filtros
        if ($request->has('periodo_id')) {
            $query->where('periodo_facturacion_id', $request->periodo_id);
        }

        if ($request->has('estado_pago')) {
            $query->where('estado_pago', $request->estado_pago);
        }

        if ($request->has('propiedad_id')) {
            $query->where('propiedad_id', $request->propiedad_id);
        }

        $expensas = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Períodos para filtro
        $periodos = PeriodoFacturacion::orderBy('mes_periodo', 'desc')
            ->limit(12)
            ->get();

        return Inertia::render('Expensas/Index', [
            'expensas' => $expensas,
            'periodos' => $periodos,
            'filtros' => $request->only(['periodo_id', 'estado_pago', 'propiedad_id'])
        ]);
    }

    public function show(Expensa $expensa)
    {
        $expensa->load([
            'propiedad',
            'periodoFacturacion',
            'pagos.usuarioRegistro',
            'usuarioGeneracion'
        ]);

        return Inertia::render('Expensas/Show', [
            'expensa' => $expensa,
            'puede_pagar' => !$expensa->estaPagada(),
            'esta_vencida' => $expensa->estaVencida()
        ]);
    }

    /**
     * Generar todas las expensas de un período
     */
    public function generar(PeriodoFacturacion $periodo)
    {
        try {
            $resultados = $this->calculoService->generarExpensasPeriodo(
                $periodo,
                auth()->id()
            );

            $mensaje = "Se generaron {$resultados['exitosas']} expensas exitosamente.";
            
            if (count($resultados['errores']) > 0) {
                $mensaje .= " Errores: " . implode('; ', $resultados['errores']);
            }

            return redirect()
                ->route('periodos.show', $periodo)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al generar expensas: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Recalcular una expensa específica
     */
    public function recalcular(Expensa $expensa)
    {
        try {
            $this->calculoService->recalcularExpensa($expensa);

            return back()->with('success', 'Expensa recalculada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Registrar un pago
     */
    public function registrarPago(Request $request, Expensa $expensa)
    {
        $validated = $request->validate([
            'monto_bs' => 'required|numeric|min:0.01|max:' . $expensa->saldo_bs,
            'fecha_pago' => 'required|date|before_or_equal:today',
            'metodo_pago' => 'required|in:efectivo,transferencia,deposito,cheque,qr,otro',
            'numero_comprobante' => 'nullable|string|max:100',
            'referencia' => 'nullable|string|max:200',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        try {
            $pago = $expensa->registrarPago($validated['monto_bs'], [
                ...$validated,
                'usuario_registro_id' => auth()->id()
            ]);

            return back()->with('success', 'Pago registrado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al registrar pago: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reporte de expensas pendientes
     */
    public function pendientes()
    {
        $expensas = Expensa::with(['propiedad', 'periodoFacturacion'])
            ->where('estado_pago', '!=', 'pagado')
            ->orderBy('fecha_vencimiento', 'asc')
            ->get()
            ->groupBy('propiedad_id');

        return Inertia::render('Expensas/Pendientes', [
            'expensas_agrupadas' => $expensas
        ]);
    }

    /**
     * Reporte de expensas vencidas
     */
    public function vencidas()
    {
        $expensas = Expensa::with(['propiedad', 'periodoFacturacion'])
            ->where('fecha_vencimiento', '<', now())
            ->where('estado_pago', '!=', 'pagado')
            ->orderBy('fecha_vencimiento', 'asc')
            ->get();

        return Inertia::render('Expensas/Vencidas', [
            'expensas' => $expensas,
            'total_deuda' => $expensas->sum('saldo_bs')
        ]);
    }

    /**
     * Historial de una propiedad
     */
    public function historialPropiedad($propiedadId)
    {
        $expensas = Expensa::with(['periodoFacturacion', 'pagos'])
            ->where('propiedad_id', $propiedadId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'expensas' => $expensas,
            'estadisticas' => [
                'total_expensas' => $expensas->count(),
                'total_pagado' => $expensas->sum('monto_pagado_bs'),
                'total_pendiente' => $expensas->sum('saldo_bs'),
                'promedio_consumo' => round($expensas->avg('consumo_m3'), 2)
            ]
        ]);
    }
}