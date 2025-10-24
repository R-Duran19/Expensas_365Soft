<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PeriodoFacturacion extends Model
{
    protected $table = 'periodos_facturacion';
    
    protected $fillable = [
        'mes_periodo',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'factor_comercial',
        'factor_domiciliario',
        'usuario_creacion_id',
        'usuario_cierre_id',
        'fecha_cierre',
        'observaciones'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_cierre' => 'datetime',
        'factor_comercial' => 'decimal:4',
        'factor_domiciliario' => 'decimal:4'
    ];

    /**
     * Relaciones
     */
    public function facturasPrincipales(): HasMany
    {
        return $this->hasMany(FacturaPrincipal::class);
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class, 'mes_periodo', 'mes_periodo');
    }

    public function expensas(): HasMany
    {
        return $this->hasMany(Expensa::class);
    }

    public function usuarioCreacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_creacion_id');
    }

    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cierre_id');
    }

    /**
     * Scopes
     */
    public function scopeAbiertos($query)
    {
        return $query->where('estado', 'abierto');
    }

    public function scopeCerrados($query)
    {
        return $query->where('estado', 'cerrado');
    }

    public function scopeCalculados($query)
    {
        return $query->where('estado', 'calculado');
    }

    /**
     * Verificar si está abierto
     */
    public function estaAbierto(): bool
    {
        return $this->estado === 'abierto';
    }

    /**
     * Verificar si puede cerrarse
     */
    public function puedeCerrarse(): bool
    {
        // Debe estar abierto
        if (!$this->estaAbierto()) {
            return false;
        }

        // Debe tener las 3 facturas registradas
        $comerciales = $this->facturasPrincipales()->where('tipo', 'comercial')->count();
        $domiciliarias = $this->facturasPrincipales()->where('tipo', 'domiciliario')->count();

        if ($comerciales !== 1 || $domiciliarias !== 2) {
            return false;
        }

        // Debe tener factores calculados
        if (!$this->factor_comercial || !$this->factor_domiciliario) {
            return false;
        }

        return true;
    }

    /**
     * Calcular factores automáticamente
     */
    public function calcularFactores(): void
    {
        // Factor Comercial
        $facturasComerciales = $this->facturasPrincipales()
            ->where('tipo', 'comercial')
            ->get();

        if ($facturasComerciales->count() === 1) {
            $factura = $facturasComerciales->first();
            if ($factura->consumo_m3 > 0) {
                $this->factor_comercial = round(
                    $factura->importe_bs / $factura->consumo_m3,
                    4
                );
            }
        }

        // Factor Domiciliario
        $facturasDomiciliarias = $this->facturasPrincipales()
            ->where('tipo', 'domiciliario')
            ->get();

        if ($facturasDomiciliarias->count() === 2) {
            $importeTotal = $facturasDomiciliarias->sum('importe_bs');
            $consumoTotal = $facturasDomiciliarias->sum('consumo_m3');

            if ($consumoTotal > 0) {
                $this->factor_domiciliario = round(
                    $importeTotal / $consumoTotal,
                    4
                );
            }
        }

        $this->save();
    }

    /**
     * Cerrar período
     */
    public function cerrar(int $usuarioId): bool
    {
        if (!$this->puedeCerrarse()) {
            return false;
        }

        $this->estado = 'cerrado';
        $this->usuario_cierre_id = $usuarioId;
        $this->fecha_cierre = now();
        
        return $this->save();
    }

    /**
     * Reabrir período
     */
    public function reabrir(): bool
    {
        if ($this->estado !== 'cerrado') {
            return false;
        }

        $this->estado = 'abierto';
        $this->usuario_cierre_id = null;
        $this->fecha_cierre = null;
        
        return $this->save();
    }

    /**
     * Obtener resumen del período
     */
    public function getResumenAttribute(): array
    {
        return [
            'facturas_registradas' => $this->facturasPrincipales()->count(),
            'lecturas_registradas' => $this->lecturas()->count(),
            'expensas_generadas' => $this->expensas()->count(),
            'monto_total_expensas' => $this->expensas()->sum('monto_total_bs'),
            'monto_pagado' => $this->expensas()->sum('monto_pagado_bs'),
            'saldo_pendiente' => $this->expensas()->sum('saldo_bs')
        ];
    }

    /**
     * Formatear período
     */
    public function getPeriodoFormateadoAttribute(): string
    {
        try {
            $fecha = Carbon::createFromFormat('Y-m', $this->mes_periodo);
            return $fecha->locale('es')->isoFormat('MMMM YYYY');
        } catch (\Exception $e) {
            return $this->mes_periodo;
        }
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        // Evitar eliminar períodos con datos
        static::deleting(function ($periodo) {
            if ($periodo->lecturas()->count() > 0) {
                throw new \Exception('No se puede eliminar un período con lecturas registradas.');
            }

            if ($periodo->expensas()->count() > 0) {
                throw new \Exception('No se puede eliminar un período con expensas generadas.');
            }
        });
    }
}