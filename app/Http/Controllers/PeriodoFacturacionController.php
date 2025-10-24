<?php

namespace App\Http\Controllers;

use App\Models\PeriodoFacturacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PeriodoFacturacionController extends Controller
{
    public function index()
    {
        $periodos = PeriodoFacturacion::with(['usuarioCreacion', 'usuarioCierre'])
            ->withCount(['facturasPrincipales', 'lecturas', 'expensas'])
            ->orderBy('mes_periodo', 'desc')
            ->paginate(15);

        return Inertia::render('Periodos/Index', [
            'periodos' => $periodos
        ]);
    }

    public function create()
    {
        // Sugerir próximo período
        $ultimoPeriodo = PeriodoFacturacion::orderBy('mes_periodo', 'desc')->first();
        
        $sugerencia = $ultimoPeriodo 
            ? Carbon::createFromFormat('Y-m', $ultimoPeriodo->mes_periodo)->addMonth()->format('Y-m')
            : Carbon::now()->format('Y-m');

        return Inertia::render('Periodos/Create', [
            'mes_sugerido' => $sugerencia
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mes_periodo' => 'required|string|regex:/^\d{4}-\d{2}$/|unique:periodos_facturacion,mes_periodo',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        $periodo = PeriodoFacturacion::create([
            ...$validated,
            'estado' => 'abierto',
            'usuario_creacion_id' => auth()->id()
        ]);

        return redirect()
            ->route('periodos.show', $periodo)
            ->with('success', "Período {$periodo->periodo_formateado} creado exitosamente.");
    }

    public function show(PeriodoFacturacion $periodo)
    {
        $periodo->load([
            'facturasPrincipales',
            'usuarioCreacion',
            'usuarioCierre'
        ]);

        // Contar lecturas por tipo
        $estadisticasLecturas = [
            'total' => $periodo->lecturas()->count(),
            'comerciales' => $periodo->lecturas()
                ->whereHas('medidor', fn($q) => $q->comerciales())
                ->count(),
            'domiciliarias' => $periodo->lecturas()
                ->whereHas('medidor', fn($q) => $q->domiciliarios())
                ->count()
        ];

        return Inertia::render('Periodos/Show', [
            'periodo' => $periodo,
            'resumen' => $periodo->resumen,
            'estadisticas_lecturas' => $estadisticasLecturas,
            'puede_cerrarse' => $periodo->puedeCerrarse()
        ]);
    }

    public function cerrar(PeriodoFacturacion $periodo)
    {
        if (!$periodo->puedeCerrarse()) {
            return back()->withErrors([
                'error' => 'El período no puede cerrarse. Faltan datos requeridos.'
            ]);
        }

        $periodo->cerrar(auth()->id());

        return back()->with('success', 'Período cerrado exitosamente.');
    }

    public function reabrir(PeriodoFacturacion $periodo)
    {
        if (!$periodo->reabrir()) {
            return back()->withErrors([
                'error' => 'No se puede reabrir este período.'
            ]);
        }

        return back()->with('success', 'Período reabierto exitosamente.');
    }

    public function destroy(PeriodoFacturacion $periodo)
    {
        try {
            $mesPeriodo = $periodo->mes_periodo;
            $periodo->delete();

            return redirect()
                ->route('periodos.index')
                ->with('success', "Período {$mesPeriodo} eliminado exitosamente.");
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }
}