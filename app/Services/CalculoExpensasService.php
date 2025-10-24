<?php

namespace App\Services;

use App\Models\PeriodoFacturacion;
use App\Models\Expensa;
use App\Models\Medidor;
use App\Models\Lectura;
use App\Models\GrupoMedidor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalculoExpensasService
{
    /**
     * Generar expensas para todo el edificio en un período
     */
    public function generarExpensasPeriodo(PeriodoFacturacion $periodo, int $usuarioId): array
    {
        if (!$periodo->puedeCerrarse()) {
            throw new \Exception('El período no tiene todos los datos necesarios para generar expensas.');
        }

        DB::beginTransaction();
        
        try {
            $resultados = [
                'exitosas' => 0,
                'errores' => [],
                'expensas_generadas' => []
            ];

            // 1. Generar expensas para medidores individuales
            $medidoresIndividuales = Medidor::individuales()
                ->activos()
                ->with(['propiedad', 'lecturaDelPeriodo'])
                ->get();

            foreach ($medidoresIndividuales as $medidor) {
                try {
                    $expensa = $this->generarExpensaMedidorIndividual(
                        $medidor,
                        $periodo,
                        $usuarioId
                    );

                    $resultados['exitosas']++;
                    $resultados['expensas_generadas'][] = $expensa->id;
                } catch (\Exception $e) {
                    $resultados['errores'][] = "Medidor {$medidor->numero_medidor}: {$e->getMessage()}";
                }
            }

            // 2. Generar expensas para medidores compartidos
            $grupos = GrupoMedidor::activos()
                ->with(['medidor', 'propiedades'])
                ->get();

            foreach ($grupos as $grupo) {
                try {
                    $expensasGrupo = $this->generarExpensasGrupoCompartido(
                        $grupo,
                        $periodo,
                        $usuarioId
                    );

                    $resultados['exitosas'] += count($expensasGrupo);
                    $resultados['expensas_generadas'] = array_merge(
                        $resultados['expensas_generadas'],
                        array_column($expensasGrupo, 'id')
                    );
                } catch (\Exception $e) {
                    $resultados['errores'][] = "Grupo {$grupo->nombre}: {$e->getMessage()}";
                }
            }

            // 3. Marcar período como calculado
            $periodo->estado = 'calculado';
            $periodo->save();

            DB::commit();

            return $resultados;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generar expensa para un medidor individual
     */
    protected function generarExpensaMedidorIndividual(
        Medidor $medidor, 
        PeriodoFacturacion $periodo,
        int $usuarioId
    ): Expensa {
        // Obtener lectura del período
        $lectura = Lectura::where('medidor_id', $medidor->id)
            ->where('mes_periodo', $periodo->mes_periodo)
            ->first();

        if (!$lectura) {
            throw new \Exception("No existe lectura para este medidor en el período {$periodo->mes_periodo}");
        }

        // Obtener factor según tipo
        $factor = $medidor->tipo === 'comercial' 
            ? $periodo->factor_comercial 
            : $periodo->factor_domiciliario;

        if (!$factor) {
            throw new \Exception("No hay factor calculado para tipo {$medidor->tipo}");
        }

        // Calcular monto
        $consumo = $lectura->consumo;
        $montoAgua = round($consumo * $factor, 2);

        // Verificar si ya existe expensa
        $expensaExistente = Expensa::where('periodo_facturacion_id', $periodo->id)
            ->where('propiedad_id', $medidor->propiedad_id)
            ->first();

        if ($expensaExistente) {
            throw new \Exception("Ya existe una expensa para esta propiedad en este período");
        }

        // Crear expensa
        return Expensa::create([
            'periodo_facturacion_id' => $periodo->id,
            'propiedad_id' => $medidor->propiedad_id,
            'consumo_m3' => $consumo,
            'monto_agua_bs' => $montoAgua,
            'factor_aplicado' => $factor,
            'monto_total_bs' => $montoAgua, // Por ahora solo agua
            'estado_pago' => 'pendiente',
            'monto_pagado_bs' => 0,
            'saldo_bs' => $montoAgua,
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays(15), // 15 días para pagar
            'usuario_generacion_id' => $usuarioId
        ]);
    }

    /**
     * Generar expensas para un grupo de medidores compartidos
     */
    protected function generarExpensasGrupoCompartido(
        GrupoMedidor $grupo,
        PeriodoFacturacion $periodo,
        int $usuarioId
    ): array {
        // Obtener lectura del medidor compartido
        $lectura = Lectura::where('medidor_id', $grupo->medidor_id)
            ->where('mes_periodo', $periodo->mes_periodo)
            ->first();

        if (!$lectura) {
            throw new \Exception("No existe lectura para el medidor compartido");
        }

        // Obtener factor según tipo del medidor
        $factor = $grupo->medidor->tipo === 'comercial'
            ? $periodo->factor_comercial
            : $periodo->factor_domiciliario;

        if (!$factor) {
            throw new \Exception("No hay factor calculado");
        }

        // Calcular consumo total y monto
        $consumoTotal = $lectura->consumo;
        $montoTotalAgua = round($consumoTotal * $factor, 2);

        // Distribuir consumo según método del grupo
        $distribucion = $grupo->calcularDistribucion($consumoTotal);

        // Crear expensas para cada propiedad
        $expensas = [];

        foreach ($distribucion as $propiedadId => $consumoPorPropiedad) {
            $montoPorPropiedad = round($consumoPorPropiedad * $factor, 2);

            $expensa = Expensa::create([
                'periodo_facturacion_id' => $periodo->id,
                'propiedad_id' => $propiedadId,
                'consumo_m3' => round($consumoPorPropiedad),
                'monto_agua_bs' => $montoPorPropiedad,
                'factor_aplicado' => $factor,
                'monto_total_bs' => $montoPorPropiedad,
                'estado_pago' => 'pendiente',
                'monto_pagado_bs' => 0,
                'saldo_bs' => $montoPorPropiedad,
                'fecha_emision' => now(),
                'fecha_vencimiento' => now()->addDays(15),
                'usuario_generacion_id' => $usuarioId,
                'observaciones' => "Medidor compartido: {$grupo->nombre}"
            ]);

            $expensas[] = $expensa;
        }

        return $expensas;
    }

    /**
     * Recalcular una expensa específica
     */
    public function recalcularExpensa(Expensa $expensa): Expensa
    {
        if ($expensa->estado_pago === 'pagado') {
            throw new \Exception('No se puede recalcular una expensa ya pagada');
        }

        $periodo = $expensa->periodoFacturacion;
        $propiedad = $expensa->propiedad;

        // Buscar medidor de la propiedad
        $medidor = Medidor::where('propiedad_id', $propiedad->id)->first();

        if (!$medidor) {
            throw new \Exception('No se encontró medidor para esta propiedad');
        }

        // Obtener lectura
        $lectura = Lectura::where('medidor_id', $medidor->id)
            ->where('mes_periodo', $periodo->mes_periodo)
            ->first();

        if (!$lectura) {
            throw new \Exception('No existe lectura para recalcular');
        }

        // Obtener factor
        $factor = $medidor->tipo === 'comercial'
            ? $periodo->factor_comercial
            : $periodo->factor_domiciliario;

        // Recalcular
        $consumo = $lectura->consumo;
        $montoAgua = round($consumo * $factor, 2);

        $expensa->update([
            'consumo_m3' => $consumo,
            'monto_agua_bs' => $montoAgua,
            'factor_aplicado' => $factor,
            'monto_total_bs' => $montoAgua,
            'saldo_bs' => $montoAgua - $expensa->monto_pagado_bs
        ]);

        return $expensa->fresh();
    }
}