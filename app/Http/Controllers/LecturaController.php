<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Medidor;
use App\Models\ExpensePeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LecturaController extends Controller
{
    /**
     * Listar lecturas con filtros
     */
    public function index(Request $request)
    {
        $query = Lectura::with([
            'medidor.propiedad.tipoPropiedad',
            'usuario',
            'expensePeriod' // Cargar relación con período
        ]);

        // Variable para verificar si hay filtros aplicados
        $tieneFiltros = false;

        // Filtro por period_id (único método)
        if ($request->filled('period_id')) {
            $query->where('period_id', $request->period_id);
            $tieneFiltros = true;
        }

        // Filtro por medidor
        if ($request->filled('medidor_id')) {
            $query->where('medidor_id', $request->medidor_id);
            $tieneFiltros = true;
        }

        // Filtro por rango de fechas
        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $query->whereBetween('fecha_lectura', [
                $request->fecha_desde,
                $request->fecha_hasta
            ]);
            $tieneFiltros = true;
        }

        // Si NO hay filtros, devolver colección vacía
        if (!$tieneFiltros) {
            $lecturas = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                10,
                1,
                ['path' => request()->url()]
            );
        } else {
            // Si HAY filtros, ejecutar query
            $lecturas = $query
                ->orderBy('fecha_lectura', 'desc')
                ->paginate(10)
                ->appends($request->only(['period_id', 'medidor_id', 'fecha_desde', 'fecha_hasta']))
                ->through(function ($lectura) {
                    $tipoPropiedadId = $lectura->medidor->propiedad->tipo_propiedad_id;
                    $tipo = in_array($tipoPropiedadId, [3, 4]) ? 'comercial' : 'domiciliario';

                    return [
                        'id' => $lectura->id,
                        'medidor_id' => $lectura->medidor_id,
                        'lectura_actual' => $lectura->lectura_actual,
                        'lectura_anterior' => $lectura->lectura_anterior,
                        'consumo' => $lectura->consumo,
                        'fecha_lectura' => $lectura->fecha_lectura,
                        'period_id' => $lectura->period_id,
                        'periodo_formateado' => $lectura->periodo_formateado,
                        'observaciones' => $lectura->observaciones,
                        'created_at' => $lectura->created_at,
                        'medidor' => [
                            'id' => $lectura->medidor->id,
                            'numero_medidor' => $lectura->medidor->numero_medidor,
                            'ubicacion' => $lectura->medidor->ubicacion,
                            'tipo' => $tipo,
                            'propiedad' => [
                                'id' => $lectura->medidor->propiedad->id,
                                'codigo' => $lectura->medidor->propiedad->codigo,
                                'nombre' => $lectura->medidor->propiedad->nombre,
                                'ubicacion' => $lectura->medidor->propiedad->ubicacion,
                                'tipo_propiedad' => $lectura->medidor->propiedad->tipoPropiedad
                            ]
                        ],
                        'usuario' => [
                            'id' => $lectura->usuario->id,
                            'name' => $lectura->usuario->name
                        ]
                    ];
                });
        }

        // Obtener períodos disponibles desde expense_periods
        $periodos = ExpensePeriod::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($period) {
                return [
                    'id' => $period->id,
                    'nombre' => $period->getPeriodName(),
                    'mes_periodo' => $period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT)
                ];
            });

  
        // Obtener período activo
        $periodoActivo = ExpensePeriod::where('status', 'open')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        return Inertia::render('Lecturas', [
            'lecturas' => $lecturas,
            'periodos' => $periodos,
            'filtros' => $request->only(['period_id', 'medidor_id', 'fecha_desde', 'fecha_hasta']),
            'tieneFiltros' => $tieneFiltros,
            'periodoActivo' => $periodoActivo ? [
                'id' => $periodoActivo->id,
                'nombre' => $periodoActivo->getPeriodName(),
                'mes_periodo' => $periodoActivo->year . '-' . str_pad($periodoActivo->month, 2, '0', STR_PAD_LEFT)
            ] : null
        ]);
    }


    public function create()
    {
        $medidores = Medidor::with(['propiedad', 'propiedad.tipoPropiedad'])
            ->activos()
            ->get()
            ->sortBy(function ($medidor) {
                return $medidor->propiedad ? $medidor->propiedad->codigo : 'ZZZ';
            })
            ->values() // reindexar el array
            ->map(function ($medidor) {
                $ultimaLectura = $medidor->ultimaLectura();
                $propiedad = $medidor->propiedad;

                return [
                    'id' => $medidor->id,
                    'numero_medidor' => $medidor->numero_medidor,
                    'ubicacion' => $propiedad ? $propiedad->ubicacion : $medidor->ubicacion,
                    'propiedad' => $propiedad ? $propiedad->codigo : 'Sin propiedad',
                    'ultima_lectura' => $ultimaLectura?->lectura_actual,
                    'fecha_ultima_lectura' => $ultimaLectura?->fecha_lectura,
                    'tipo' => $medidor->tipo
                ];
            });

        // Obtener el período activo actual
        $periodoActivo = ExpensePeriod::where('status', 'open')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        return Inertia::render('Lecturas/Create', [
            'medidores' => $medidores,
            'periodoActivo' => $periodoActivo ? [
                'id' => $periodoActivo->id,
                'nombre' => $periodoActivo->getPeriodName(),
                'mes_periodo' => $periodoActivo->year . '-' . str_pad($periodoActivo->month, 2, '0', STR_PAD_LEFT)
            ] : null
        ]);
    }

    /**
     * Guardar nueva lectura
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'medidor_id' => 'required|exists:medidores,id',
            'lectura_actual' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,3})?$/',
            'fecha_lectura' => 'required|date|before_or_equal:today',
            'period_id' => 'required|exists:expense_periods,id',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $medidor = Medidor::findOrFail($validated['medidor_id']);

            // Verificar que el medidor esté activo
            if (!$medidor->activo) {
                throw ValidationException::withMessages([
                    'medidor_id' => 'El medidor seleccionado no está activo.'
                ]);
            }

            // Verificar duplicados usando period_id
            $existeLectura = Lectura::where('medidor_id', $validated['medidor_id'])
                ->where('period_id', $validated['period_id'])
                ->exists();

            if ($existeLectura) {
                $period = ExpensePeriod::find($validated['period_id']);
                throw ValidationException::withMessages([
                    'period_id' => "Ya existe una lectura para este medidor en el período {$period->getPeriodName()}."
                ]);
            }

            // Obtener última lectura
            $ultimaLectura = $medidor->ultimaLectura();
            $lecturaAnterior = $ultimaLectura ? $ultimaLectura->lectura_actual : 0;

            // Validar que no haya regresión (lectura menor a la anterior)
            if ($validated['lectura_actual'] < $lecturaAnterior) {
                throw ValidationException::withMessages([
                    'lectura_actual' => sprintf(
                        'La lectura actual (%d) no puede ser menor que la lectura anterior (%d). Posible retroceso del medidor.',
                        $validated['lectura_actual'],
                        $lecturaAnterior
                    )
                ]);
            }

            // Validar consumo anormal (opcional: alertar si hay consumo muy alto)
            $consumo = $validated['lectura_actual'] - $lecturaAnterior;
            $umbralAlerta = 100; // m³ - ajusta según necesites

            if ($consumo > $umbralAlerta) {
                // Solo alertar, no bloquear
                session()->flash('warning', sprintf(
                    'Consumo alto detectado: %d m³. Verifica que la lectura sea correcta.',
                    $consumo
                ));
            }

            // Obtener mes_periodo para compatibilidad
            $period = ExpensePeriod::find($validated['period_id']);
            $mesPeriodo = $period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT);

            // Crear lectura
            $lectura = Lectura::create([
                'medidor_id' => $validated['medidor_id'],
                'lectura_actual' => $validated['lectura_actual'],
                'lectura_anterior' => $lecturaAnterior,
                'fecha_lectura' => $validated['fecha_lectura'],
                'period_id' => $validated['period_id'],
                'mes_periodo' => $mesPeriodo, // Mantener para compatibilidad
                'usuario_id' => auth()->id(),
                'observaciones' => $validated['observaciones']
            ]);

            DB::commit();

            return redirect()
                ->route('lecturas.index')
                ->with('success', sprintf(
                    'Lectura registrada exitosamente. Consumo: %d m³',
                    $lectura->consumo
                ));
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar lectura: ' . $e->getMessage()]);
        }
    }

    /**
     * Ver detalle de una lectura
     */
    public function show(Lectura $lectura)
    {
        $lectura->load(['medidor.propiedad', 'usuario']);

        return Inertia::render('Lecturas/Show', [
            'lectura' => [
                ...$lectura->toArray(),
                'puede_eliminar' => $lectura->puedeSerEliminada(),
                'es_ultima' => $lectura->esUltimaLectura(),
                'lectura_previa' => $lectura->lecturaPrevia(),
                'lectura_siguiente' => $lectura->lecturaSiguiente()
            ]
        ]);
    }

    /**
     * Obtener última lectura de un medidor (API)
     */
    public function getUltimaLectura(Medidor $medidor)
    {
        $ultimaLectura = $medidor->ultimaLectura();

        // ✅ Retornar directamente sin el wrapper 'data'
        return response()->json([
            'lectura_anterior' => $ultimaLectura ? $ultimaLectura->lectura_actual : 0,
            'fecha_ultima_lectura' => $ultimaLectura?->fecha_lectura,
            'period_id_anterior' => $ultimaLectura?->period_id
        ]);
    }

    /**
     * Eliminar lectura (solo si es la última)
     */
    public function destroy(Lectura $lectura)
    {
        try {
            if (!$lectura->puedeSerEliminada()) {
                return back()->withErrors([
                    'error' => 'Solo se puede eliminar la última lectura del medidor.'
                ]);
            }

            $medidor = $lectura->medidor->numero_medidor;
            $lectura->delete();

            return redirect()
                ->route('lecturas.index')
                ->with('success', "Lectura del medidor {$medidor} eliminada exitosamente.");
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Error al eliminar lectura: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de lecturas
     */
    public function estadisticas(Request $request)
    {
        $periodoId = $request->input('period_id');

        if (!$periodoId) {
            return response()->json(['error' => 'period_id es requerido'], 400);
        }

        $stats = [
            'total_lecturas' => Lectura::delPeriodoId($periodoId)->count(),
            'consumo_total' => Lectura::delPeriodoId($periodoId)->sum('consumo'),
            'consumo_promedio' => Lectura::delPeriodoId($periodoId)->avg('consumo'),
            'consumo_maximo' => Lectura::delPeriodoId($periodoId)->max('consumo'),
            'lecturas_pendientes' => Medidor::activos()->count() -
                Lectura::delPeriodoId($periodoId)->distinct('medidor_id')->count()
        ];

        return response()->json($stats);
    }

    /**
     * Registrar lecturas masivas (múltiples medidores a la vez)
     */
    public function storeMasivo(Request $request)
    {
        $validated = $request->validate([
            'fecha_lectura' => 'required|date|before_or_equal:today',
            'period_id' => 'required|exists:expense_periods,id',
            'lecturas' => 'required|array|min:1',
            'lecturas.*.medidor_id' => 'required|exists:medidores,id',
            'lecturas.*.lectura_actual' => 'required|numeric|min:0',
            'lecturas.*.observaciones' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            // Obtener mes_periodo para compatibilidad
            $period = ExpensePeriod::find($validated['period_id']);
            $mesPeriodo = $period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT);

            $lecturas_creadas = 0;
            $errores = [];

            foreach ($validated['lecturas'] as $index => $lecturaData) {
                try {
                    $medidor = Medidor::findOrFail($lecturaData['medidor_id']);
                    $ultimaLectura = $medidor->ultimaLectura();
                    $lecturaAnterior = $ultimaLectura ? $ultimaLectura->lectura_actual : 0;

                    // Verificar duplicado
                    if (Lectura::where('medidor_id', $lecturaData['medidor_id'])
                        ->where('period_id', $validated['period_id'])
                        ->exists()
                    ) {
                        $errores[] = "Medidor {$medidor->numero_medidor}: ya existe lectura para este período";
                        continue;
                    }

                    // Validar regresión
                    if ($lecturaData['lectura_actual'] < $lecturaAnterior) {
                        $errores[] = "Medidor {$medidor->numero_medidor}: lectura menor a la anterior";
                        continue;
                    }

                    Lectura::create([
                        'medidor_id' => $lecturaData['medidor_id'],
                        'lectura_actual' => $lecturaData['lectura_actual'],
                        'lectura_anterior' => $lecturaAnterior,
                        'fecha_lectura' => $validated['fecha_lectura'],
                        'period_id' => $validated['period_id'],
                        'mes_periodo' => $mesPeriodo, // Mantener para compatibilidad
                        'usuario_id' => auth()->id(),
                        'observaciones' => $lecturaData['observaciones'] ?? null
                    ]);

                    $lecturas_creadas++;
                } catch (\Exception $e) {
                    $errores[] = "Error en lectura " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            $mensaje = "Se registraron {$lecturas_creadas} lecturas exitosamente.";
            if (count($errores) > 0) {
                $mensaje .= " Errores: " . implode('; ', $errores);
            }

            return redirect()
                ->route('lecturas.index')
                ->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar lecturas: ' . $e->getMessage()]);
        }
    }
}
