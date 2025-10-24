<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';
    
    protected $fillable = [
        'expensa_id',
        'monto_bs',
        'fecha_pago',
        'metodo_pago',
        'numero_comprobante',
        'referencia',
        'usuario_registro_id',
        'observaciones'
    ];

    protected $casts = [
        'monto_bs' => 'decimal:2',
        'fecha_pago' => 'date'
    ];

    /**
     * Relaciones
     */
    public function expensa(): BelongsTo
    {
        return $this->belongsTo(Expensa::class);
    }

    public function usuarioRegistro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }

    /**
     * Scopes
     */
    public function scopeDelPeriodo($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_pago', [$desde, $hasta]);
    }

    public function scopePorMetodo($query, string $metodo)
    {
        return $query->where('metodo_pago', $metodo);
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        // No permitir eliminar pagos (solo anular)
        static::deleting(function ($pago) {
            throw new \Exception('No se pueden eliminar pagos. Use anulación en su lugar.');
        });
    }
}