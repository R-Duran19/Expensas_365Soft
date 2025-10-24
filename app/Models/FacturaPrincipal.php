<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaPrincipal extends Model
{
    protected $table = 'facturas_principales';
    
    protected $fillable = [
        'periodo_facturacion_id',
        'tipo',
        'numero_medidor_empresa',
        'importe_bs',
        'consumo_m3',
        'fecha_emision',
        'fecha_vencimiento',
        'usuario_registro_id',
        'observaciones'
    ];

    protected $casts = [
        'importe_bs' => 'decimal:2',
        'consumo_m3' => 'integer',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date'
    ];

    /**
     * Relaciones
     */
    public function periodoFacturacion(): BelongsTo
    {
        return $this->belongsTo(PeriodoFacturacion::class);
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    /**
     * Scopes
     */
    public function scopeComerciales($query)
    {
        return $query->where('tipo', 'comercial');
    }

    public function scopeDomiciliarias($query)
    {
        return $query->where('tipo', 'domiciliario');
    }

    public function scopeDelPeriodo($query, string $periodo)
    {
        return $query->whereHas('periodoFacturacion', function ($q) use ($periodo) {
            $q->where('mes_periodo', $periodo);
        });
    }

    /**
     * Calcular factor de esta factura
     */
    public function getFactor(): float
    {
        if ($this->consumo_m3 == 0) {
            return 0;
        }

        return round($this->importe_bs / $this->consumo_m3, 4);
    }

    /**
     * Verificar si es comercial
     */
    public function esComercial(): bool
    {
        return $this->tipo === 'comercial';
    }

    /**
     * Verificar si es domiciliaria
     */
    public function esDomiciliaria(): bool
    {
        return $this->tipo === 'domiciliario';
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        // Después de crear/actualizar, recalcular factores del período
        static::saved(function ($factura) {
            $factura->periodoFacturacion->calcularFactores();
        });

        // Después de eliminar, recalcular factores
        static::deleted(function ($factura) {
            $factura->periodoFacturacion->calcularFactores();
        });
    }
}