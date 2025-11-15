<?php

namespace App\Http\Controllers;

use App\Models\FacturaMedidorPrincipal;
use App\Models\ExpensePeriod;
use App\Models\PeriodoFacturacion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class FacturaMedidorPrincipalController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    //     $this->middleware('verified');
    // }

    /**
     * Mostrar vista principal con selector de período
     */
    public function index(Request $request): Response
    {
        // Si no hay período seleccionado, mostrar selector
        if (!$request->has('mes_periodo')) {
            // Obtener períodos con facturas registradas
            $periodosConFacturas = FacturaMedidorPrincipal::select('mes_periodo')
                ->distinct()
                ->orderBy('mes_periodo', 'desc')
                ->pluck('mes_periodo');

            // Obtener períodos disponibles desde ExpensePeriod (consistente con el sistema de expensas)
            $expensePeriods = ExpensePeriod::orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $periodosDisponibles = $expensePeriods->filter(function ($period) use ($periodosConFacturas) {
                $mesPeriodo = $period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT);
                return !$periodosConFacturas->contains($mesPeriodo) && $period->status === 'open';
            })->map(function ($period) {
                return [
                    'mes_periodo' => $period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT),
                    'periodo_formateado' => $period->getPeriodName(),
                ];
            });

            return Inertia::render('FacturasMedidoresPrincipales/PeriodSelector', [
                'periodos_con_facturas' => $periodosConFacturas->map(function ($periodo) {
                    $resumen = FacturaMedidorPrincipal::getResumenPeriodo($periodo);
                    return [
                        'mes_periodo' => $periodo,
                        'periodo_formateado' => $this->formatPeriodo($periodo),
                        'resumen' => $resumen,
                    ];
                }),
                'periodos_disponibles' => $periodosDisponibles,
            ]);
        }

        // Mostrar facturas del período seleccionado
        $request->validate([
            'mes_periodo' => 'required|string|regex:/^\d{4}-\d{2}$/'
        ]);

        $mesPeriodo = $request->mes_periodo;

        $facturas = FacturaMedidorPrincipal::where('mes_periodo', $mesPeriodo)
            ->orderBy('tipo')
            ->orderBy('created_at')
            ->get()
            ->map(function ($factura) {
                return [
                    'id' => $factura->id,
                    'numero_medidor' => $factura->numero_medidor,
                    'tipo' => $factura->tipo,
                    'tipo_formateado' => ucfirst($factura->tipo),
                    'importe_bs' => $factura->importe_bs,
                    'consumo_m3' => $factura->consumo_m3,
                    'factor_calculado' => $factura->factor_calculado,
                    'fecha_emision' => $factura->fecha_emision?->format('d/m/Y'),
                    'fecha_vencimiento' => $factura->fecha_vencimiento?->format('d/m/Y'),
                    'observaciones' => $factura->observaciones,
                    'created_at' => $factura->created_at->format('d/m/Y H:i'),
                ];
            });

        $resumen = FacturaMedidorPrincipal::getResumenPeriodo($mesPeriodo);
        $periodoFacturacion = PeriodoFacturacion::where('mes_periodo', $mesPeriodo)->first();

        return Inertia::render('FacturasMedidoresPrincipales/Index', [
            'mes_periodo' => $mesPeriodo,
            'periodo_formateado' => $this->formatPeriodo($mesPeriodo),
            'facturas' => $facturas,
            'resumen' => $resumen,
            'periodo_facturacion' => $periodoFacturacion ? [
                'factor_comercial' => $periodoFacturacion->factor_comercial,
                'factor_domiciliario' => $periodoFacturacion->factor_domiciliario,
                'estado' => $periodoFacturacion->estado,
            ] : null,
        ]);
    }

    /**
     * Mostrar formulario para crear facturas de un período
     */
    public function create(Request $request): Response
    {
        $request->validate([
            'mes_periodo' => 'required|string|regex:/^\d{4}-\d{2}$/'
        ]);

        $mesPeriodo = $request->mes_periodo;

        // Verificar que el ExpensePeriod exista y esté abierto
        $year = intval(substr($mesPeriodo, 0, 4));
        $month = intval(substr($mesPeriodo, 5, 2));

        $expensePeriod = ExpensePeriod::where('year', $year)
            ->where('month', $month)
            ->where('status', 'open')
            ->first();

        if (!$expensePeriod) {
            // Si no existe el período, mostrar error o redireccionar
            return redirect()->back()->with('error', 'El período seleccionado no existe o no está abierto.');
        }

        // Crear o actualizar PeriodoFacturacion para mantener compatibilidad
        $periodoFacturacion = PeriodoFacturacion::firstOrCreate(
            ['mes_periodo' => $mesPeriodo],
            [
                'estado' => 'abierto',
                'fecha_inicio' => \Carbon\Carbon::createFromFormat('Y-m', $mesPeriodo)->startOfMonth(),
                'fecha_fin' => \Carbon\Carbon::createFromFormat('Y-m', $mesPeriodo)->endOfMonth(),
                'usuario_creacion_id' => auth()->id(),
            ]
        );

        // Obtener facturas existentes del período
        $facturasExistentes = FacturaMedidorPrincipal::where('mes_periodo', $mesPeriodo)
            ->get()
            ->map(function ($factura) {
                return [
                    'id' => $factura->id,
                    'numero_medidor' => $factura->numero_medidor,
                    'tipo' => $factura->tipo,
                    'importe_bs' => $factura->importe_bs,
                    'consumo_m3' => $factura->consumo_m3,
                    'factor_calculado' => $factura->factor_calculado,
                ];
            });

        return Inertia::render('FacturasMedidoresPrincipales/Create', [
            'mes_periodo' => $mesPeriodo,
            'periodo_formateado' => $this->formatPeriodo($mesPeriodo),
            'facturas_existentes' => $facturasExistentes,
            'periodo_facturacion' => [
                'factor_comercial' => $periodoFacturacion->factor_comercial,
                'factor_domiciliario' => $periodoFacturacion->factor_domiciliario,
            ],
        ]);
    }

    /**
     * Guardar facturas de medidores principales
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'mes_periodo' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'facturas' => 'required|array|min:1',
            'facturas.*.numero_medidor' => 'required|string|max:50',
            'facturas.*.tipo' => 'required|in:comercial,domiciliario',
            'facturas.*.importe_bs' => 'required|numeric|min:0',
            'facturas.*.consumo_m3' => 'required|numeric|min:0',
            'facturas.*.fecha_emision' => 'nullable|date',
            'facturas.*.fecha_vencimiento' => 'nullable|date|after_or_equal:facturas.*.fecha_emision',
            'facturas.*.observaciones' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $facturasCreadas = 0;
            $facturasActualizadas = 0;

            foreach ($request->facturas as $facturaData) {
                $factura = FacturaMedidorPrincipal::updateOrCreate(
                    [
                        'mes_periodo' => $request->mes_periodo,
                        'numero_medidor' => $facturaData['numero_medidor'],
                    ],
                    [
                        'tipo' => $facturaData['tipo'],
                        'importe_bs' => $facturaData['importe_bs'],
                        'consumo_m3' => $facturaData['consumo_m3'],
                        'fecha_emision' => $facturaData['fecha_emision'],
                        'fecha_vencimiento' => $facturaData['fecha_vencimiento'],
                        'observaciones' => $facturaData['observaciones'],
                        'usuario_registro_id' => auth()->id(),
                    ]
                );

                if ($factura->wasRecentlyCreated) {
                    $facturasCreadas++;
                } else {
                    $facturasActualizadas++;
                }
            }

            // Obtener resumen actualizado
            $resumen = FacturaMedidorPrincipal::getResumenPeriodo($request->mes_periodo);

            DB::commit();

            return response()->json([
                'message' => 'Facturas guardadas correctamente',
                'status' => 'success',
                'facturas_creadas' => $facturasCreadas,
                'facturas_actualizadas' => $facturasActualizadas,
                'resumen' => $resumen,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al guardar facturas: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Eliminar una factura
     */
    public function destroy(FacturaMedidorPrincipal $factura): JsonResponse
    {
        try {
            $factura->delete();

            return response()->json([
                'message' => 'Factura eliminada correctamente',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar factura: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Obtener resumen de un período (API endpoint)
     */
    public function getResumen(Request $request): JsonResponse
    {
        $request->validate([
            'mes_periodo' => 'required|string|regex:/^\d{4}-\d{2}$/'
        ]);

        $resumen = FacturaMedidorPrincipal::getResumenPeriodo($request->mes_periodo);

        return response()->json([
            'status' => 'success',
            'resumen' => $resumen
        ]);
    }

    /**
     * Formatear período para mostrar
     */
    private function formatPeriodo(string $periodo): string
    {
        try {
            $fecha = \Carbon\Carbon::createFromFormat('Y-m', $periodo);
            return $fecha->locale('es')->isoFormat('MMMM YYYY');
        } catch (\Exception $e) {
            return $periodo;
        }
    }
}