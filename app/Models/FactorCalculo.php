<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactorCalculo extends Model
{
    protected $table = 'factores_calculo';

    protected $fillable = [
        'tipo_propiedad_id',
        'factor',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'factor' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Obtener el tipo de propiedad asociado
     */
    public function tipoPropiedad(): BelongsTo
    {
        return $this->belongsTo(TipoPropiedad::class, 'tipo_propiedad_id');
    }

    /**
     * Scope para factores activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->whereNull('fecha_fin');
    }

    /**
     * Scope para factores vigentes en una fecha
     */
    public function scopeVigenteEn($query, $fecha = null)
    {
        $fecha = $fecha ?? now();
        
        return $query->where('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_fin')
                  ->orWhere('fecha_fin', '>=', $fecha);
            });
    }
}