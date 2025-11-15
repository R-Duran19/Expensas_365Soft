<?php

namespace App\Services;

use App\Models\PropertyExpense;
use App\Models\PropertyExpenseDetail;
use App\Models\ExpensePeriod;
use App\Models\Propiedad;
use App\Models\Propietario;
use App\Models\TipoPropiedad;
use App\Models\Medidor;
use App\Models\Lectura;
use App\Models\FactorCalculo;
use App\Models\WaterFactor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseCalculatorService
{
    /**
     * Generar expensas consolidadas para todos los propietarios activos de un período
     */
    public function generateForPeriod(ExpensePeriod $period, array $factors): array
    {
        $results = [
            'generated_expenses' => 0,
            'skipped_properties' => 0,
            'properties_without_readings' => 0,
            'total_amount' => 0,
            'errors' => [],
            'details' => []
        ];

        try {
            DB::beginTransaction();

            // Obtener todos los propietarios con propiedades activas (sin eager loading)
            $propietariosConPropiedades = DB::table('propietario_propiedad')
                ->whereNull('fecha_fin')
                ->distinct()
                ->pluck('propietario_id');

            if ($propietariosConPropiedades->isEmpty()) {
                $results['errors'][] = "No hay propietarios activos con propiedades asignadas";
                DB::commit();
                return $results;
            }

            $results['total_properties'] = $propietariosConPropiedades->count();

            // Calcular factores de agua y obtener el water_factor_id
            $waterFactors = $this->calculateWaterFactors($period);
            $allFactors = array_merge($factors, $waterFactors);
            $waterFactorId = $waterFactors['water_factor_id'] ?? null;

            // Generar expensa consolidada para cada propietario activo
            foreach ($propietariosConPropiedades as $propietarioId) {
                // Verificar si ya existe expensa para este propietario en este período
                $existingExpense = PropertyExpense::where('expense_period_id', $period->id)
                    ->where('propietario_id', $propietarioId)
                    ->first();

                if ($existingExpense) {
                    $results['skipped_properties']++;
                    continue;
                }

                // Calcular expensa consolidada para este propietario
                $consolidatedExpense = $this->calculateConsolidatedExpenseForOwner($propietarioId, $period, $allFactors);

                if ($consolidatedExpense['success']) {
                    // Crear la expensa consolidada
                    $expense = PropertyExpense::create([
                        'water_factor_id' => $waterFactorId,
                        'expense_period_id' => $period->id,
                        'propiedad_id' => $consolidatedExpense['primary_property_id'],
                        'propietario_id' => $consolidatedExpense['propietario_id'],
                        'base_amount' => $consolidatedExpense['base_amount'],
                        'water_amount' => $consolidatedExpense['water_amount'],
                        'other_amount' => 0,
                        'previous_debt' => $consolidatedExpense['previous_debt'],
                        'total_amount' => $consolidatedExpense['total_amount'],
                        'balance' => $consolidatedExpense['total_amount'],
                        'status' => 'pending',
                        'due_date' => $this->calculateDueDate($period),
                        'notes' => $consolidatedExpense['notes'],
                    ]);

                    // Guardar detalles individuales de cada propiedad
                    $this->savePropertyExpenseDetails($expense, $consolidatedExpense['properties_details']);

                    $results['generated_expenses']++;
                    $results['total_amount'] += $consolidatedExpense['total_amount'];

                    $results['details'][] = [
                        'property_code' => $consolidatedExpense['properties_summary'],
                        'owner' => $consolidatedExpense['propietario_nombre'],
                        'properties_count' => $consolidatedExpense['properties_count'],
                        'base_amount' => $consolidatedExpense['base_amount'],
                        'water_amount' => $consolidatedExpense['water_amount'],
                        'previous_debt' => $consolidatedExpense['previous_debt'],
                        'total_amount' => $consolidatedExpense['total_amount'],
                        'has_water_readings' => $consolidatedExpense['has_water_readings'],
                    ];

                    if (!$consolidatedExpense['has_water_readings']) {
                        $results['properties_without_readings']++;
                    }

                } else {
                    $results['errors'][] = "Propietario ID {$propietarioId}: " . $consolidatedExpense['message'];
                    $results['skipped_properties']++;
                }
            }

            // Actualizar totales del período
            $period->update([
                'total_generated' => DB::table('property_expenses')
                    ->where('expense_period_id', $period->id)
                    ->sum('total_amount')
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = "Error general: " . $e->getMessage();
            Log::error("Error en generateForPeriod: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Calcular expensa consolidada para un propietario específico
     */
    private function calculateConsolidatedExpenseForOwner(int $propietarioId, ExpensePeriod $period, array $factors): array
    {
        try {
            // Obtener información del propietario
            $propietario = Propietario::find($propietarioId);
            if (!$propietario) {
                return [
                    'success' => false,
                    'message' => 'Propietario no encontrado'
                ];
            }

            // Obtener propiedades activas de este propietario (consultas directas para evitar eager loading)
            $propiedadesIds = DB::table('propietario_propiedad')
                ->where('propietario_id', $propietarioId)
                ->whereNull('fecha_fin')
                ->pluck('propiedad_id');

            if ($propiedadesIds->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'El propietario no tiene propiedades activas'
                ];
            }

            $propiedades = Propiedad::whereIn('id', $propiedadesIds)
                ->where('activo', true)
                ->get();

            if ($propiedades->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No hay propiedades activas para este propietario'
                ];
            }

            // Variables para acumular los totales de los detalles ya redondeados
            $propertiesDetails = [];
            $totalBaseAmountFromDetails = 0;
            $totalWaterAmountFromDetails = 0;
            $hasWaterReadings = false;

            foreach ($propiedades as $propiedad) {
                // Obtener tipo de propiedad y factor
                $tipoPropiedad = TipoPropiedad::find($propiedad->tipo_propiedad_id);
                $esComercial = $tipoPropiedad ? ($tipoPropiedad->nombre === 'Comercial' || $tipoPropiedad->nombre === 'Oficina') : false;

                // Obtener factor de cálculo
                $factorCalculo = FactorCalculo::where('tipo_propiedad_id', $propiedad->tipo_propiedad_id)
                    ->where('activo', true)
                    ->whereNull('fecha_fin')
                    ->first();

                $factor = $factorCalculo ? $factorCalculo->factor : ($esComercial ? ($factors['factor_comercial'] ?? 3.5) : ($factors['factor_departamento'] ?? 2.1));

                // Calcular monto base y redondearlo por propiedad
                $baseAmount = $propiedad->metros_cuadrados * $factor;
                $baseAmountRedondeado = $this->roundToNearestInteger($baseAmount);

                // Calcular consumo de agua (detallado)
                $waterData = $this->calculateDetailedWaterAmount($propiedad->id, $period, $factors);
                $waterAmount = $waterData['amount']; // Ya viene redondeado del método

                if ($waterAmount > 0) {
                    $hasWaterReadings = true;
                }

                // Obtener medidor para detalles
                $medidor = Medidor::where('propiedad_id', $propiedad->id)->first();
                $waterFactor = $esComercial ? ($factors['factor_agua_comercial'] ?? 0) : ($factors['factor_agua_domiciliario'] ?? 0);

                // Calcular total para esta propiedad
                $totalAmountRedondeado = $baseAmountRedondeado + $waterAmount;

                // Acumular totales desde los detalles ya redondeados
                $totalBaseAmountFromDetails += $baseAmountRedondeado;
                $totalWaterAmountFromDetails += $waterAmount;

                // Log detallado por propiedad
                Log::debug("Propiedad {$propiedad->codigo} (ID: {$propiedad->id}):");
                Log::debug("  Base: {$baseAmount} → {$baseAmountRedondeado} BS");
                Log::debug("  Agua: {$waterAmount} BS");
                Log::debug("  Total propiedad: {$totalAmountRedondeado} BS");

                $propertiesDetails[] = [
                    'propiedad_id' => $propiedad->id,
                    'codigo' => $propiedad->codigo,
                    'ubicacion' => $propiedad->ubicacion,
                    'metros_cuadrados' => $propiedad->metros_cuadrados,
                    'tipo_propiedad' => $tipoPropiedad->nombre ?? 'Desconocido',
                    'factor_expensas' => $factor, // Factor para expensas comunes
                    'factor_agua' => $waterFactor, // Factor para agua
                    'factor_calculado' => $factor, // Factor real utilizado
                    'base_amount' => $baseAmountRedondeado,
                    'water_amount' => $waterAmount, // Ya viene redondeado del método calculateDetailedWaterAmount
                    'total_amount' => $totalAmountRedondeado,
                    'water_consumption_m3' => $waterData['consumption_m3'],
                    'water_previous_reading' => $waterData['previous_reading'],
                    'water_current_reading' => $waterData['current_reading'],
                    'water_medidor_codigo' => $waterData['medidor_codigo'] ?? ($medidor->numero_medidor ?? null),
                ];
            }

            // Calcular deuda anterior
            $previousDebt = $this->calculatePreviousDebt($propietarioId, $period);

            // Usar los totales exactos de los detalles (ya redondeados)
            $totalBaseAmount = $totalBaseAmountFromDetails;
            $totalWaterAmount = $totalWaterAmountFromDetails;

            // Calcular total con montos ya redondeados de detalles
            $totalAmount = $totalBaseAmount + $totalWaterAmount + $previousDebt;

            // Logs para verificar consistencia
            Log::info("Resumen cálculo para propietario {$propietarioId}:");
            Log::info("  Base (desde detalles): {$totalBaseAmount} BS");
            Log::info("  Agua (desde detalles): {$totalWaterAmount} BS");
            Log::info("  Deuda anterior: {$previousDebt} BS");
            Log::info("  Total final: {$totalAmount} BS");
            Log::info("  Propiedades procesadas: " . count($propertiesDetails));

            // Crear resumen de propiedades
            $propertiesSummary = $propiedades->pluck('codigo')->implode(', ');

            // Generar notas
            $notes = "Expensa consolidada por {$propiedades->count()} propiedad(es): {$propertiesSummary}";

            return [
                'success' => true,
                'propietario_id' => $propietario->id,
                'propietario_nombre' => $propietario->nombre_completo,
                'primary_property_id' => $propiedades->first()->id,
                'properties_count' => $propiedades->count(),
                'properties_summary' => $propertiesSummary,
                'base_amount' => $totalBaseAmount,
                'water_amount' => $totalWaterAmount,
                'previous_debt' => $previousDebt,
                'total_amount' => $totalAmount,
                'has_water_readings' => $hasWaterReadings,
                'notes' => $notes,
                'properties_details' => $propertiesDetails,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error calculando expensa: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Guardar detalles individuales de cada propiedad en la expensa consolidada
     */
    private function savePropertyExpenseDetails(PropertyExpense $expense, array $propertiesDetails): void
    {
        foreach ($propertiesDetails as $detail) {
            PropertyExpenseDetail::create([
                'water_factor_id' => $expense->water_factor_id,
                'property_expense_id' => $expense->id,
                'propiedad_id' => $detail['propiedad_id'],
                'propiedad_codigo' => $detail['codigo'],
                'propiedad_ubicacion' => $detail['ubicacion'],
                'metros_cuadrados' => $detail['metros_cuadrados'],
                'tipo_propiedad' => $detail['tipo_propiedad'],
                'factor_expensas' => $detail['factor_expensas'],
                'factor_agua' => $detail['factor_agua'],
                'factor_calculado' => $detail['factor_calculado'],
                'base_amount' => $detail['base_amount'],
                'water_amount' => $detail['water_amount'],
                'total_amount' => $detail['total_amount'],
                'water_consumption_m3' => $detail['water_consumption_m3'],
                'water_previous_reading' => $detail['water_previous_reading'],
                'water_current_reading' => $detail['water_current_reading'],
                'water_medidor_codigo' => $detail['water_medidor_codigo'],
            ]);
        }
    }

    /**
     * Calcular consumo de agua detallado (usando period_id y requiere_medidor)
     */
    private function calculateDetailedWaterAmount(int $propiedadId, ExpensePeriod $period, array $factors): array
    {
        $defaultData = [
            'amount' => 0,
            'consumption_m3' => 0,
            'previous_reading' => null,
            'current_reading' => null,
            'medidor_codigo' => null,
            'requiere_medidor' => false,
        ];

        try {
            // Obtener información de la propiedad y su tipo
            $propiedad = Propiedad::find($propiedadId);
            if (!$propiedad) {
                Log::error("No se encontró propiedad {$propiedadId}");
                return $defaultData;
            }

            $tipoPropiedad = TipoPropiedad::find($propiedad->tipo_propiedad_id);
            if (!$tipoPropiedad) {
                Log::error("No se encontró tipo de propiedad para {$propiedadId}");
                return $defaultData;
            }

            // Si el tipo de propiedad no requiere medidor, no calcular agua
            if (!$tipoPropiedad->requiere_medidor) {
                Log::info("Propiedad {$propiedadId} ({$tipoPropiedad->nombre}) no requiere medidor");
                return [
                    ...$defaultData,
                    'requiere_medidor' => false,
                ];
            }

            // Verificar si la propiedad tiene medidor
            $medidor = Medidor::where('propiedad_id', $propiedadId)->first();
            if (!$medidor) {
                Log::warning("Propiedad {$propiedadId} requiere medidor pero no tiene uno asignado");
                return [
                    ...$defaultData,
                    'requiere_medidor' => true,
                ];
            }

            // Obtener lectura actual usando period_id
            $currentLectura = Lectura::where('medidor_id', $medidor->id)
                ->where('period_id', $period->id)
                ->first();

            if (!$currentLectura) {
                Log::warning("No se encontró lectura actual para medidor {$medidor->id} en período {$period->id}");
                return [
                    ...$defaultData,
                    'requiere_medidor' => true,
                    'medidor_codigo' => $medidor->numero_medidor,
                ];
            }

            // Obtener lectura del período anterior
            $previousPeriod = ExpensePeriod::where('year', $period->year)
                ->where('month', $period->month - 1)
                ->when($period->month == 1, function($query) use ($period) {
                    return $query->where('year', $period->year - 1)->where('month', 12);
                })
                ->first();

            $previousReading = null;
            if ($previousPeriod) {
                $previousLectura = Lectura::where('medidor_id', $medidor->id)
                    ->where('period_id', $previousPeriod->id)
                    ->first();

                if ($previousLectura) {
                    $previousReading = $previousLectura->lectura_actual;
                }
            }

            // Usar el consumo ya calculado en la tabla lecturas (columna virtual)
            // Esto evita recálculos y asegura consistencia con los datos registrados
            $consumption = $currentLectura->consumo;

            // Obtener lecturas para mostrar en frontend (sin recalcular consumo)
            $currentReading = $currentLectura->lectura_actual;
            $previousReading = $currentLectura->lectura_anterior ?? 0;

            // Validar que el consumo no sea negativo
            if ($consumption < 0) {
                Log::warning("Consumo negativo detectado para medidor {$medidor->id}: {$consumption} m³");
                return [
                    ...$defaultData,
                    'requiere_medidor' => true,
                    'medidor_codigo' => $medidor->numero_medidor,
                ];
            }

            // Determinar factor según tipo de propiedad
            $esComercial = $tipoPropiedad->esComercial();
            $factor = $esComercial
                ? ($factors['factor_agua_comercial'] ?? 0)
                : ($factors['factor_agua_domiciliario'] ?? 0);

            if ($factor <= 0) {
                Log::warning("Factor de agua no disponible para tipo " . ($esComercial ? 'comercial' : 'domiciliario'));
                return [
                    ...$defaultData,
                    'requiere_medidor' => true,
                    'medidor_codigo' => $medidor->numero_medidor,
                    'consumption_m3' => $consumption,
                    'previous_reading' => $previousReading,
                    'current_reading' => $currentReading,
                ];
            }

            $amount = $consumption * $factor;

            // Aplicar redondeo al monto de agua
            $amountRedondeado = $this->roundToNearestInteger($amount);

            Log::info("Agua calculada para propiedad {$propiedadId}: {$consumption} m³ × {$factor} = {$amount} BS → Redondeado: {$amountRedondeado} BS");

            return [
                'amount' => $amountRedondeado,
                'amount_raw' => $amount, // Valor sin redondear para debugging
                'consumption_m3' => $consumption,
                'previous_reading' => $previousReading,
                'current_reading' => $currentReading,
                'medidor_codigo' => $medidor->numero_medidor,
                'requiere_medidor' => true,
                'factor_aplicado' => $factor,
            ];

        } catch (\Exception $e) {
            Log::error("Error calculando agua para propiedad {$propiedadId}: " . $e->getMessage());
            return $defaultData;
        }
    }

    /**
     * Calcular consumo de agua simplificado (usando period_id y requiere_medidor)
     */
    private function calculateSimpleWaterAmount(int $propiedadId, ExpensePeriod $period, array $factors): float
    {
        try {
            // Obtener información de la propiedad y su tipo
            $propiedad = Propiedad::find($propiedadId);
            if (!$propiedad) {
                Log::error("No se encontró propiedad {$propiedadId}");
                return 0;
            }

            $tipoPropiedad = TipoPropiedad::find($propiedad->tipo_propiedad_id);
            if (!$tipoPropiedad) {
                Log::error("No se encontró tipo de propiedad para {$propiedadId}");
                return 0;
            }

            // Si el tipo de propiedad no requiere medidor, no calcular agua
            if (!$tipoPropiedad->requiere_medidor) {
                return 0;
            }

            // Verificar si la propiedad tiene medidor
            $medidor = Medidor::where('propiedad_id', $propiedadId)->first();
            if (!$medidor) {
                return 0;
            }

            // Obtener lectura actual usando period_id
            $currentLectura = Lectura::where('medidor_id', $medidor->id)
                ->where('period_id', $period->id)
                ->first();

            if (!$currentLectura) {
                return 0;
            }

            // Obtener lectura del período anterior
            $previousPeriod = ExpensePeriod::where('year', $period->year)
                ->where('month', $period->month - 1)
                ->when($period->month == 1, function($query) use ($period) {
                    return $query->where('year', $period->year - 1)->where('month', 12);
                })
                ->first();

            $previousReading = 0;
            if ($previousPeriod) {
                $previousLectura = Lectura::where('medidor_id', $medidor->id)
                    ->where('period_id', $previousPeriod->id)
                    ->first();

                if ($previousLectura) {
                    $previousReading = $previousLectura->lectura_actual;
                }
            }

            // Usar el consumo ya calculado en la tabla lecturas (columna virtual)
            $consumption = $currentLectura->consumo;
            if ($consumption <= 0) {
                return 0;
            }

            // Determinar factor según tipo de propiedad
            $esComercial = $tipoPropiedad->esComercial();
            $factor = $esComercial
                ? ($factors['factor_agua_comercial'] ?? 0)
                : ($factors['factor_agua_domiciliario'] ?? 0);

            if ($factor <= 0) {
                return 0;
            }

            return $consumption * $factor;

        } catch (\Exception $e) {
            Log::error("Error calculando agua para propiedad {$propiedadId}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular y guardar factores de agua desde facturas de medidores principales
     */
    private function calculateWaterFactors(ExpensePeriod $period): array
    {
        try {
            $periodoString = $period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT);

            // Buscar factores existentes
            $waterFactor = WaterFactor::where('expense_period_id', $period->id)->first();

            if ($waterFactor && $waterFactor->hasValidFactors()) {
                Log::info("Usando factores existentes para período {$periodoString}");
                return [
                    'factor_agua_comercial' => $waterFactor->factor_comercial,
                    'factor_agua_domiciliario' => $waterFactor->factor_domiciliario,
                    'water_factor_id' => $waterFactor->id
                ];
            }

            // Calcular factores desde facturas de medidores principales
            $facturas = DB::table('facturas_medidores_principales')
                ->where('mes_periodo', $periodoString)
                ->get();

            if ($facturas->isEmpty()) {
                Log::warning("No hay facturas de medidores principales para el período {$periodoString}");
                return [
                    'factor_agua_comercial' => 0,
                    'factor_agua_domiciliario' => 0,
                    'water_factor_id' => null
                ];
            }

            $totalConsumoComercial = $facturas->where('tipo', 'comercial')->sum('consumo_m3');
            $totalMontoComercial = $facturas->where('tipo', 'comercial')->sum('importe_bs');

            $totalConsumoDomiciliario = $facturas->where('tipo', 'domiciliario')->sum('consumo_m3');
            $totalMontoDomiciliario = $facturas->where('tipo', 'domiciliario')->sum('importe_bs');

            $factorComercial = $totalConsumoComercial > 0 ? $totalMontoComercial / $totalConsumoComercial : 0;
            $factorDomiciliario = $totalConsumoDomiciliario > 0 ? $totalMontoDomiciliario / $totalConsumoDomiciliario : 0;

            // Guardar factores en la base de datos
            $waterFactor = WaterFactor::create([
                'expense_period_id' => $period->id,
                'factor_comercial' => $factorComercial,
                'factor_domiciliario' => $factorDomiciliario,
                'total_consumo_comercial' => $totalConsumoComercial,
                'total_importe_comercial' => $totalMontoComercial,
                'total_consumo_domiciliario' => $totalConsumoDomiciliario,
                'total_importe_domiciliario' => $totalMontoDomiciliario,
                'usuario_calculo_id' => auth()->id(),
                'notas' => "Calculado automáticamente desde facturas de medidores principales - {$periodoString}",
            ]);

            Log::info("Factores guardados para período {$periodoString}:");
            Log::info("  Comercial: {$factorComercial} (Monto: {$totalMontoComercial} / Consumo: {$totalConsumoComercial})");
            Log::info("  Domiciliario: {$factorDomiciliario} (Monto: {$totalMontoDomiciliario} / Consumo: {$totalConsumoDomiciliario})");

            return [
                'factor_agua_comercial' => $factorComercial,
                'factor_agua_domiciliario' => $factorDomiciliario,
                'water_factor_id' => $waterFactor->id
            ];

        } catch (\Exception $e) {
            Log::error("Error calculando factores de agua para período {$period->year}-{$period->month}: " . $e->getMessage());
            return [
                'factor_agua_comercial' => 0,
                'factor_agua_domiciliario' => 0,
                'water_factor_id' => null
            ];
        }
    }

    /**
     * Calcular deuda anterior de un propietario
     */
    private function calculatePreviousDebt(int $propietarioId, ExpensePeriod $period): float
    {
        try {
            // Obtener expensas pendientes de períodos anteriores
            $previousExpenses = PropertyExpense::where('propietario_id', $propietarioId)
                ->whereHas('expensePeriod', function ($query) use ($period) {
                    $query->where(function ($q) use ($period) {
                        $q->where('year', '<', $period->year)
                          ->orWhere(function ($subQ) use ($period) {
                              $subQ->where('year', $period->year)
                                   ->where('month', '<', $period->month);
                          });
                    });
                })
                ->where('status', '!=', 'paid')
                ->get();

            return $previousExpenses->sum('balance');
        } catch (\Exception $e) {
            Log::error("Error calculando deuda anterior para propietario {$propietarioId}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcular fecha de vencimiento (15 días después del inicio del período)
     */
    private function calculateDueDate(ExpensePeriod $period): string
    {
        return Carbon::create($period->year, $period->month, 15)->format('Y-m-d');
    }

    /**
     * Redondear según reglas del negocio (más de 50 centavos hacia arriba)
     */
    private function roundToNearestInteger(float $amount): float
    {
        $decimal = $amount - floor($amount);
        return $decimal >= 0.50 ? ceil($amount) : floor($amount);
    }

    /**
     * Calcular expensa para una propiedad específica (preview)
     */
    public function calculateProperty(int $propertyId, int $periodId, array $factors): array
    {
        try {
            $property = Propiedad::find($propertyId);
            if (!$property) {
                return [
                    'success' => false,
                    'message' => 'Propiedad no encontrada'
                ];
            }

            // Obtener propietario activo
            $propietarioId = DB::table('propietario_propiedad')
                ->where('propiedad_id', $propertyId)
                ->whereNull('fecha_fin')
                ->value('propietario_id');

            if (!$propietarioId) {
                return [
                    'success' => false,
                    'message' => 'La propiedad no tiene propietario activo asignado'
                ];
            }

            $propietario = Propietario::find($propietarioId);
            $period = ExpensePeriod::find($periodId);

            if (!$propietario || !$period) {
                return [
                    'success' => false,
                    'message' => 'No se encontró propietario o período'
                ];
            }

            // Calcular factores de agua
            $waterFactors = $this->calculateWaterFactors($period);
            $allFactors = array_merge($factors, $waterFactors);

            // Usar la misma lógica que en el cálculo consolidado
            $tipoPropiedad = TipoPropiedad::find($property->tipo_propiedad_id);
            $esComercial = $tipoPropiedad ? ($tipoPropiedad->nombre === 'Comercial' || $tipoPropiedad->nombre === 'Oficina') : false;

            $factorCalculo = FactorCalculo::where('tipo_propiedad_id', $property->tipo_propiedad_id)
                ->where('activo', true)
                ->whereNull('fecha_fin')
                ->first();

            $factor = $factorCalculo ? $factorCalculo->factor : ($esComercial ? ($factors['factor_comercial'] ?? 3.5) : ($factors['factor_departamento'] ?? 2.1));

            $baseAmount = $property->metros_cuadrados * $factor;
            $waterAmount = $this->calculateSimpleWaterAmount($propertyId, $period, $allFactors);
            $previousDebt = $this->calculatePreviousDebt($propietarioId, $period);
            $totalAmount = $baseAmount + $waterAmount + $previousDebt;

            // Redondear
            $baseAmount = $this->roundToNearestInteger($baseAmount);
            $waterAmount = $this->roundToNearestInteger($waterAmount);
            $totalAmount = $this->roundToNearestInteger($totalAmount);

            return [
                'success' => true,
                'propietario_id' => $propietario->id,
                'propietario_nombre' => $propietario->nombre_completo,
                'base_amount' => $baseAmount,
                'water_amount' => $waterAmount,
                'other_amount' => 0,
                'previous_debt' => $previousDebt,
                'total_amount' => $totalAmount,
                'property_codigo' => $property->codigo,
                'property_ubicacion' => $property->ubicacion,
                'metros_cuadrados' => $property->metros_cuadrados,
                'factor' => $factor,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error calculando expensa: ' . $e->getMessage()
            ];
        }
    }
}