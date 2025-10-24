<?php

namespace App\Http\Controllers;

use App\Models\Lectura;
use App\Models\Medidor;
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
            'usuario'
        ]);

        // Variable para verificar si hay filtros aplicados
        $tieneFiltros = false;

        // Filtro por mes/período
        if ($request->filled('periodo')) {
            $query->where('mes_periodo', $request->periodo);
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
                ->appends($request->only(['periodo', 'medidor_id', 'fecha_desde', 'fecha_hasta']))
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
                        'mes_periodo' => $lectura->mes_periodo,
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

        // Obtener períodos disponibles
        $periodos = Lectura::select('mes_periodo')
            ->distinct()
            ->orderBy('mes_periodo', 'desc')
            ->pluck('mes_periodo');

        return Inertia::render('Lecturas', [
            'lecturas' => $lecturas,
            'periodos' => $periodos,
            'filtros' => $request->only(['periodo', 'medidor_id', 'fecha_desde', 'fecha_hasta']),
            'tieneFiltros' => $tieneFiltros // Nuevo
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

        $mesActual = Carbon::now()->format('Y-m');

        return Inertia::render('Lecturas/Create', [
            'medidores' => $medidores,
            'mesActual' => $mesActual
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
            'mes_periodo' => 'required|string|regex:/^\d{4}-\d{2}$/',
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

            // Verificar duplicados
            $existeLectura = Lectura::where('medidor_id', $validated['medidor_id'])
                ->where('mes_periodo', $validated['mes_periodo'])
                ->exists();

            if ($existeLectura) {
                throw ValidationException::withMessages([
                    'mes_periodo' => 'Ya existe una lectura para este medidor en el período seleccionado.'
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

            // Crear lectura
            $lectura = Lectura::create([
                'medidor_id' => $validated['medidor_id'],
                'lectura_actual' => $validated['lectura_actual'],
                'lectura_anterior' => $lecturaAnterior,
                'fecha_lectura' => $validated['fecha_lectura'],
                'mes_periodo' => $validated['mes_periodo'],
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
            'mes_periodo_anterior' => $ultimaLectura?->mes_periodo
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
        $periodo = $request->input('periodo', Carbon::now()->format('Y-m'));

        $stats = [
            'total_lecturas' => Lectura::delPeriodo($periodo)->count(),
            'consumo_total' => Lectura::delPeriodo($periodo)->sum('consumo'),
            'consumo_promedio' => Lectura::delPeriodo($periodo)->avg('consumo'),
            'consumo_maximo' => Lectura::delPeriodo($periodo)->max('consumo'),
            'lecturas_pendientes' => Medidor::activos()->count() -
                Lectura::delPeriodo($periodo)->distinct('medidor_id')->count()
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
            'mes_periodo' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'lecturas' => 'required|array|min:1',
            'lecturas.*.medidor_id' => 'required|exists:medidores,id',
            'lecturas.*.lectura_actual' => 'required|integer|min:0',
            'lecturas.*.observaciones' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $lecturas_creadas = 0;
            $errores = [];

            foreach ($validated['lecturas'] as $index => $lecturaData) {
                try {
                    $medidor = Medidor::findOrFail($lecturaData['medidor_id']);
                    $ultimaLectura = $medidor->ultimaLectura();
                    $lecturaAnterior = $ultimaLectura ? $ultimaLectura->lectura_actual : 0;

                    // Verificar duplicado
                    if (Lectura::where('medidor_id', $lecturaData['medidor_id'])
                        ->where('mes_periodo', $validated['mes_periodo'])
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
                        'mes_periodo' => $validated['mes_periodo'],
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
