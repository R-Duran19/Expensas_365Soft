<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterFactor extends Model
{
    protected $table = 'water_factors';

    protected $fillable = [
        'expense_period_id',
        'factor_comercial',
        'factor_domiciliario',
        'total_consumo_comercial',
        'total_importe_comercial',
        'total_consumo_domiciliario',
        'total_importe_domiciliario',
        'usuario_calculo_id',
        'notas',
    ];

    protected $casts = [
        'factor_comercial' => 'decimal:6',
        'factor_domiciliario' => 'decimal:6',
        'total_consumo_comercial' => 'decimal:3',
        'total_importe_comercial' => 'decimal:2',
        'total_consumo_domiciliario' => 'decimal:3',
        'total_importe_domiciliario' => 'decimal:2',
    ];

    /**
     * Relación con el período de facturación
     */
    public function expensePeriod(): BelongsTo
    {
        return $this->belongsTo(ExpensePeriod::class);
    }

    /**
     * Relación con el usuario que calculó los factores
     */
    public function usuarioCalculo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_calculo_id');
    }

    /**
     * Obtener factor según tipo de propiedad
     */
    public function getFactorByTipo(bool $esComercial): ?float
    {
        return $esComercial ? $this->factor_comercial : $this->factor_domiciliario;
    }

    /**
     * Verificar si tiene factor comercial
     */
    public function hasFactorComercial(): bool
    {
        return $this->factor_comercial && $this->factor_comercial > 0;
    }

    /**
     * Verificar si tiene factor domiciliario
     */
    public function hasFactorDomiciliario(): bool
    {
        return $this->factor_domiciliario && $this->factor_domiciliario > 0;
    }

    /**
     * Verificar si el período tiene factores válidos
     */
    public function hasValidFactors(): bool
    {
        return $this->hasFactorComercial() || $this->hasFactorDomiciliario();
    }

    /**
     * Obtener resumen del cálculo
     */
    public function getResumenCalculoAttribute(): string
    {
        $resumen = [];

        if ($this->hasFactorComercial()) {
            $resumen[] = "Comercial: {$this->factor_comercial} ({$this->total_importe_comercial} BS / {$this->total_consumo_comercial} m³)";
        }

        if ($this->hasFactorDomiciliario()) {
            $resumen[] = "Domiciliario: {$this->factor_domiciliario} ({$this->total_importe_domiciliario} BS / {$this->total_consumo_domiciliario} m³)";
        }

        return implode(' | ', $resumen);
    }

    /**
     * Scope para obtener factores de un período específico
     */
    public function scopeDelPeriodo($query, int $periodId)
    {
        return $query->where('expense_period_id', $periodId);
    }

    /**
     * Crear o actualizar factores para un período
     */
    public static function crearOActualizarParaPeriodo(int $expensePeriodId, array $factores, ?int $usuarioId = null): self
    {
        return self::updateOrCreate(
            ['expense_period_id' => $expensePeriodId],
            [
                'factor_comercial' => $factores['factor_comercial'] ?? null,
                'factor_domiciliario' => $factores['factor_domiciliario'] ?? null,
                'total_consumo_comercial' => $factores['total_consumo_comercial'] ?? 0,
                'total_importe_comercial' => $factores['total_importe_comercial'] ?? 0,
                'total_consumo_domiciliario' => $factores['total_consumo_domiciliario'] ?? 0,
                'total_importe_domiciliario' => $factores['total_importe_domiciliario'] ?? 0,
                'usuario_calculo_id' => $usuarioId,
                'notas' => $factores['notas'] ?? 'Calculado automáticamente desde facturas de medidores principales',
            ]
        );
    }
}