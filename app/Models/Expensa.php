<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expensa extends Model
{
    protected $table = 'expensas';
    
    protected $fillable = [
        'periodo_facturacion_id',
        'propiedad_id',
        'consumo_m3',
        'monto_agua_bs',
        'factor_aplicado',
        'monto_total_bs',
        'estado_pago',
        'monto_pagado_bs',
        'saldo_bs',
        'fecha_emision',
        'fecha_vencimiento',
        'usuario_generacion_id',
        'observaciones'
    ];

    protected $casts = [
        'consumo_m3' => 'integer',
        'monto_agua_bs' => 'decimal:2',
        'factor_aplicado' => 'decimal:4',
        'monto_total_bs' => 'decimal:2',
        'monto_pagado_bs' => 'decimal:2',
        'saldo_bs' => 'decimal:2',
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

    public function propiedad(): BelongsTo
    {
        return $this->belongsTo(Propiedad::class);
    }

    public function usuarioGeneracion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_generacion_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * Scopes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_pago', 'pendiente');
    }

    public function scopePagadas($query)
    {
        return $query->where('estado_pago', 'pagado');
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado_pago', 'vencido');
    }

    /**
     * Verificar si está vencida
     */
    public function estaVencida(): bool
    {
        return $this->fecha_vencimiento < now() && 
               $this->estado_pago !== 'pagado';
    }

    /**
     * Verificar si está pagada completamente
     */
    public function estaPagada(): bool
    {
        return $this->estado_pago === 'pagado';
    }

    /**
     * Registrar un pago
     */
    public function registrarPago(float $monto, array $datos): Pago
    {
        $pago = $this->pagos()->create([
            'monto_bs' => $monto,
            'fecha_pago' => $datos['fecha_pago'],
            'metodo_pago' => $datos['metodo_pago'],
            'numero_comprobante' => $datos['numero_comprobante'] ?? null,
            'referencia' => $datos['referencia'] ?? null,
            'usuario_registro_id' => $datos['usuario_registro_id'],
            'observaciones' => $datos['observaciones'] ?? null
        ]);

        // Actualizar montos
        $this->monto_pagado_bs += $monto;
        $this->saldo_bs = $this->monto_total_bs - $this->monto_pagado_bs;

        // Actualizar estado
        if ($this->saldo_bs <= 0) {
            $this->estado_pago = 'pagado';
        } elseif ($this->monto_pagado_bs > 0) {
            $this->estado_pago = 'pagado_parcial';
        }

        $this->save();

        return $pago;
    }

    /**
     * Calcular mora si está vencida
     */
    public function calcularMora(float $porcentajeMora = 0): float
    {
        if (!$this->estaVencida() || $porcentajeMora == 0) {
            return 0;
        }

        $diasVencidos = now()->diffInDays($this->fecha_vencimiento);
        return round($this->saldo_bs * ($porcentajeMora / 100) * $diasVencidos, 2);
    }

    /**
     * Actualizar estado según vencimiento
     */
    public function actualizarEstado(): void
    {
        if ($this->estaPagada()) {
            return;
        }

        if ($this->estaVencida()) {
            $this->estado_pago = 'vencido';
            $this->save();
        }
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        // Calcular saldo antes de guardar
        static::saving(function ($expensa) {
            $expensa->saldo_bs = $expensa->monto_total_bs - $expensa->monto_pagado_bs;
        });
    }
}