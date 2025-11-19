<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\PropertyExpense;

class Qr extends Model
{
    protected $table = 'qr';
    protected $fillable = [
        'alias',
        'estado',
        'pago_id',
        'monto',
        'propietario_id',
        'expensa_id',
        'imagen_qr',
        'fecha_vencimiento',
        'detalle_glosa',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_vencimiento' => 'datetime',
        'imagen_qr' => 'array',
    ];

    const ESTADO_PENDIENTE = 'PENDIENTE';
    const ESTADO_PAGADO = 'PAGADO';
    const ESTADO_VENCIDO = 'VENCIDO';
    const ESTADO_CANCELADO = 'CANCELADO';

    /**
     * Relación con el pago
     */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    /**
     * Relación con el propietario
     */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class);
    }

    /**
     * Relación con la expensa
     */
    public function expensa(): BelongsTo
    {
        return $this->belongsTo(PropertyExpense::class, 'expensa_id');
    }

    /**
     * Relación con property expense (alias)
     */
    public function propertyExpense(): BelongsTo
    {
        return $this->belongsTo(PropertyExpense::class, 'expensa_id');
    }

    /**
     * Verificar si el QR está vigente
     */
    public function estaVigente(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isFuture();
    }

    /**
     * Verificar si el QR está pagado
     */
    public function estaPagado(): bool
    {
        return $this->estado === self::ESTADO_PAGADO;
    }

    /**
     * Verificar si el QR está pendiente
     */
    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Obtener alias único para el pago
     */
    public static function generarAliasPago(int $pagoId, int $propietarioId): string
    {
        $timestamp = now()->format('YmdHis');
        $random = rand(100, 999);
        return "PAG{$pagoId}PRO{$propietarioId}T{$timestamp}{$random}";
    }

    /**
     * Scope para QRs pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para QRs de un pago específico
     */
    public function scopeDelPago($query, int $pagoId)
    {
        return $query->where('pago_id', $pagoId);
    }
}