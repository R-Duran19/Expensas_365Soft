<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyExpenseDetail extends Model
{
    protected $table = 'property_expense_details';

    protected $fillable = [
        'water_factor_id',
        'property_expense_id',
        'propiedad_id',
        'propiedad_codigo',
        'propiedad_ubicacion',
        'metros_cuadrados',
        'tipo_propiedad',
        'factor_expensas',
        'factor_agua',
        'factor_calculado',
        'base_amount',
        'water_amount',
        'total_amount',
        'water_consumption_m3',
        'water_previous_reading',
        'water_current_reading',
        'water_medidor_codigo',
    ];

    protected $casts = [
        'metros_cuadrados' => 'decimal:2',
        'factor_expensas' => 'decimal:2',
        'factor_agua' => 'decimal:6',
        'factor_calculado' => 'decimal:2',
        'base_amount' => 'integer',
        'water_amount' => 'integer',
        'total_amount' => 'integer',
        'water_consumption_m3' => 'decimal:3',
        'water_previous_reading' => 'decimal:2',
        'water_current_reading' => 'decimal:2',
    ];

    /**
     * Relación con el factor de agua
     */
    public function waterFactor(): BelongsTo
    {
        return $this->belongsTo(WaterFactor::class);
    }

    /**
     * Relación con la expensa consolidada
     */
    public function propertyExpense(): BelongsTo
    {
        return $this->belongsTo(PropertyExpense::class);
    }

    /**
     * Relación con la propiedad
     */
    public function propiedad(): BelongsTo
    {
        return $this->belongsTo(Propiedad::class);
    }

    /**
     * Obtener formatted metros cuadrados
     */
    public function getFormattedMetrosCuadradosAttribute(): string
    {
        return number_format($this->metros_cuadrados, 2) . ' m²';
    }

    /**
     * Obtener formatted consumo de agua
     */
    public function getFormattedWaterConsumptionAttribute(): string
    {
        return $this->water_consumption_m3 ? $this->water_consumption_m3 . ' m³' : 'N/A';
    }

    /**
     * Verificar si tiene medidor de agua
     */
    public function hasWaterMeter(): bool
    {
        return !is_null($this->water_medidor_codigo);
    }

    /**
     * Obtener resumen de lecturas de agua
     */
    public function getWaterReadingsSummaryAttribute(): string
    {
        if (!$this->hasWaterMeter()) {
            return 'Sin medidor';
        }

        if ($this->water_previous_reading && $this->water_current_reading) {
            return "Anterior: {$this->water_previous_reading} | Actual: {$this->water_current_reading} | Consumo: {$this->water_consumption_m3} m³";
        }

        return 'Medidor sin lecturas';
    }
}