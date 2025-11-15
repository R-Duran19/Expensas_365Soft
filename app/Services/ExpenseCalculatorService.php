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

            // Calcular factores de agua
            $waterFactors = $this->calculateWaterFactors($period);
            $allFactors = array_merge($factors, $waterFactors);

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

            // Calcular totales consolidados
            $totalBaseAmount = 0;
            $totalWaterAmount = 0;
            $hasWaterReadings = false;
            $propertiesDetails = [];

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

                // Calcular monto base
                $baseAmount = $propiedad->metros_cuadrados * $factor;
                $totalBaseAmount += $baseAmount;

                // Calcular consumo de agua (detallado)
                $waterData = $this->calculateDetailedWaterAmount($propiedad->id, $period, $factors);
                $waterAmount = $waterData['amount'];
                $totalWaterAmount += $waterAmount;

                if ($waterAmount > 0) {
                    $hasWaterReadings = true;
                }

                // Obtener medidor para detalles
                $medidor = Medidor::where('propiedad_id', $propiedad->id)->first();
                $waterFactor = $esComercial ? ($factors['factor_agua_comercial'] ?? 0) : ($factors['factor_agua_domiciliario'] ?? 0);

                $propertiesDetails[] = [
                    'propiedad_id' => $propiedad->id,
                    'codigo' => $propiedad->codigo,
                    'ubicacion' => $propiedad->ubicacion,
                    'metros_cuadrados' => $propiedad->metros_cuadrados,
                    'tipo_propiedad' => $tipoPropiedad->nombre ?? 'Desconocido',
                    'factor_expensas' => $factor, // Factor para expensas comunes
                    'factor_agua' => $waterFactor, // Factor para agua
                    'factor_calculado' => $factor, // Factor real utilizado
                    'base_amount' => $baseAmount,
                    'water_amount' => $waterAmount,
                    'total_amount' => $baseAmount + $waterAmount,
                    'water_consumption_m3' => $waterData['consumption_m3'],
                    'water_previous_reading' => $waterData['previous_reading'],
                    'water_current_reading' => $waterData['current_reading'],
                    'water_medidor_codigo' => $waterData['medidor_codigo'] ?? ($medidor->numero_medidor ?? null),
                ];
            }

            // Calcular deuda anterior
            $previousDebt = $this->calculatePreviousDebt($propietarioId, $period);

            // Calcular total
            $totalAmount = $totalBaseAmount + $totalWaterAmount + $previousDebt;

            // Redondear según reglas
            $totalBaseAmount = $this->roundToNearestInteger($totalBaseAmount);
            $totalWaterAmount = $this->roundToNearestInteger($totalWaterAmount);
            $totalAmount = $this->roundToNearestInteger($totalAmount);

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

            // Si no hay lectura anterior, usar 0 como base
            if ($previousReading === null) {
                $previousReading = 0;
                Log::info("No se encontró lectura anterior para medidor {$medidor->id}, usando 0 como base");
            }

            $consumption = $currentLectura->lectura_actual - $previousReading;

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
                    'current_reading' => $currentLectura->lectura_actual,
                ];
            }

            $amount = $consumption * $factor;

            Log::info("Agua calculada para propiedad {$propiedadId}: {$consumption} m³ × {$factor} = {$amount} BS");

            return [
                'amount' => $amount,
                'consumption_m3' => $consumption,
                'previous_reading' => $previousReading,
                'current_reading' => $currentLectura->lectura_actual,
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

            $consumption = $currentLectura->lectura_actual - $previousReading;
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
     * Calcular factores de agua desde facturas de medidores principales
     */
    private function calculateWaterFactors(ExpensePeriod $period): array
    {
        $factors = [
            'factor_agua_comercial' => 0,
            'factor_agua_domiciliario' => 0
        ];

        try {
            $periodoString = $period->year . '-' . str_pad($period->month, 2, '0', STR_PAD_LEFT);

            $facturas = DB::table('facturas_medidores_principales')
                ->where('mes_periodo', $periodoString)
                ->get();

            if ($facturas->isNotEmpty()) {
                $totalConsumoComercial = $facturas->where('tipo', 'comercial')->sum('consumo_m3');
                $totalMontoComercial = $facturas->where('tipo', 'comercial')->sum('importe_bs');

                $totalConsumoDomiciliario = $facturas->where('tipo', 'domiciliario')->sum('consumo_m3');
                $totalMontoDomiciliario = $facturas->where('tipo', 'domiciliario')->sum('importe_bs');

                if ($totalConsumoComercial > 0) {
                    $factors['factor_agua_comercial'] = $totalMontoComercial / $totalConsumoComercial;
                    Log::info("Factor comercial calculado: {$factors['factor_agua_comercial']} (Monto: {$totalMontoComercial} / Consumo: {$totalConsumoComercial})");
                } else {
                    Log::warning("No hay consumo comercial para el período {$periodoString}");
                }

                if ($totalConsumoDomiciliario > 0) {
                    $factors['factor_agua_domiciliario'] = $totalMontoDomiciliario / $totalConsumoDomiciliario;
                    Log::info("Factor domiciliario calculado: {$factors['factor_agua_domiciliario']} (Monto: {$totalMontoDomiciliario} / Consumo: {$totalConsumoDomiciliario})");
                } else {
                    Log::warning("No hay consumo domiciliario para el período {$periodoString}");
                }
            } else {
                Log::warning("No hay facturas de medidores principales para el período {$periodoString}");
            }
        } catch (\Exception $e) {
            Log::error("Error calculando factores de agua: " . $e->getMessage());
        }

        return $factors;
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