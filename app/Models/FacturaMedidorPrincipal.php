<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaMedidorPrincipal extends Model
{
    protected $table = 'facturas_medidores_principales';

    protected $fillable = [
        'mes_periodo',
        'numero_medidor',
        'tipo',
        'importe_bs',
        'consumo_m3',
        'fecha_emision',
        'fecha_vencimiento',
        'factor_calculado',
        'usuario_registro_id',
        'observaciones',
    ];

    protected $casts = [
        'importe_bs' => 'decimal:2',
        'consumo_m3' => 'decimal:3',
        'factor_calculado' => 'decimal:4',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * Usuario que registró la factura
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    /**
     * Scopes
     */
    public function scopeDelPeriodo($query, string $periodo)
    {
        return $query->where('mes_periodo', $periodo);
    }

    public function scopeComerciales($query)
    {
        return $query->where('tipo', 'comercial');
    }

    public function scopeDomiciliarias($query)
    {
        return $query->where('tipo', 'domiciliario');
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
     * Formatear período
     */
    public function getPeriodoFormateadoAttribute(): string
    {
        try {
            $fecha = \Carbon\Carbon::createFromFormat('Y-m', $this->mes_periodo);
            return $fecha->locale('es')->isoFormat('MMMM YYYY');
        } catch (\Exception $e) {
            return $this->mes_periodo;
        }
    }

    /**
     * Boot: Calcular factor automáticamente al guardar
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($factura) {
            // Calcular factor si tenemos los datos necesarios
            if ($factura->consumo_m3 > 0) {
                $factura->factor_calculado = round($factura->importe_bs / $factura->consumo_m3, 4);
            } else {
                $factura->factor_calculado = null;
            }
        });

        // Al guardar o eliminar, recalcular factores del período
        static::saved(function ($factura) {
            static::recalcularFactoresPeriodo($factura->mes_periodo);
        });

        static::deleted(function ($factura) {
            static::recalcularFactoresPeriodo($factura->mes_periodo);
        });
    }

    /**
     * Recalcular factores para un período específico
     */
    private static function recalcularFactoresPeriodo(string $mesPeriodo): void
    {
        // Buscar o crear el período de facturación correspondiente
        $periodoFacturacion = PeriodoFacturacion::firstOrCreate(
            ['mes_periodo' => $mesPeriodo],
            [
                'estado' => 'abierto',
                'fecha_inicio' => \Carbon\Carbon::createFromFormat('Y-m', $mesPeriodo)->startOfMonth(),
                'fecha_fin' => \Carbon\Carbon::createFromFormat('Y-m', $mesPeriodo)->endOfMonth(),
                'usuario_creacion_id' => auth()->id(),
            ]
        );

        // Calcular factor comercial
        $facturasComerciales = static::delPeriodo($mesPeriodo)->comerciales()->get();
        if ($facturasComerciales->isNotEmpty() && $facturasComerciales->sum('consumo_m3') > 0) {
            $periodoFacturacion->factor_comercial = round(
                $facturasComerciales->sum('importe_bs') / $facturasComerciales->sum('consumo_m3'),
                4
            );
        } else {
            $periodoFacturacion->factor_comercial = null;
        }

        // Calcular factor domiciliario (suma de todas las facturas domiciliarias)
        $facturasDomiciliarias = static::delPeriodo($mesPeriodo)->domiciliarias()->get();
        if ($facturasDomiciliarias->isNotEmpty() && $facturasDomiciliarias->sum('consumo_m3') > 0) {
            $periodoFacturacion->factor_domiciliario = round(
                $facturasDomiciliarias->sum('importe_bs') / $facturasDomiciliarias->sum('consumo_m3'),
                4
            );
        } else {
            $periodoFacturacion->factor_domiciliario = null;
        }

        $periodoFacturacion->save();
    }

    /**
     * Obtener resumen de facturas de un período
     */
    public static function getResumenPeriodo(string $mesPeriodo): array
    {
        $facturas = static::delPeriodo($mesPeriodo)->get();

        return [
            'total_facturas' => $facturas->count(),
            'comerciales' => $facturas->where('tipo', 'comercial')->count(),
            'domiciliarias' => $facturas->where('tipo', 'domiciliario')->count(),
            'importe_total_comercial' => $facturas->where('tipo', 'comercial')->sum('importe_bs'),
            'consumo_total_comercial' => $facturas->where('tipo', 'comercial')->sum('consumo_m3'),
            'importe_total_domiciliario' => $facturas->where('tipo', 'domiciliario')->sum('importe_bs'),
            'consumo_total_domiciliario' => $facturas->where('tipo', 'domiciliario')->sum('consumo_m3'),
            'factor_comercial' => $facturas->where('tipo', 'comercial')->sum('consumo_m3') > 0
                ? round($facturas->where('tipo', 'comercial')->sum('importe_bs') / $facturas->where('tipo', 'comercial')->sum('consumo_m3'), 4)
                : 0,
            'factor_domiciliario' => $facturas->where('tipo', 'domiciliario')->sum('consumo_m3') > 0
                ? round($facturas->where('tipo', 'domiciliario')->sum('importe_bs') / $facturas->where('tipo', 'domiciliario')->sum('consumo_m3'), 4)
                : 0,
        ];
    }
}