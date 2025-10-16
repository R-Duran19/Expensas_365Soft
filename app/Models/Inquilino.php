<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquilino extends Model
{
    use SoftDeletes;

    protected $table = 'inquilinos';

    protected $fillable = [
        'propiedad_id',
        'nombre_completo',
        'ci',
        'telefono',
        'email',
        'fecha_inicio_contrato',
        'fecha_fin_contrato',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio_contrato' => 'date',
        'fecha_fin_contrato' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Obtener la propiedad asociada
     */
    public function propiedad(): BelongsTo
    {
        return $this->belongsTo(Propiedad::class, 'propiedad_id');
    }

    /**
     * Scope para inquilinos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para inquilinos con contrato vigente
     */
    public function scopeContratoVigente($query)
    {
        return $query->where('fecha_inicio_contrato', '<=', now())
            ->where(function ($q) {
                $q->whereNull('fecha_fin_contrato')
                  ->orWhere('fecha_fin_contrato', '>=', now());
            });
    }

    /**
     * Verificar si el contrato está vigente
     */
    public function contratoVigente(): bool
    {
        $hoy = now();
        
        if ($this->fecha_inicio_contrato > $hoy) {
            return false;
        }

        if ($this->fecha_fin_contrato && $this->fecha_fin_contrato < $hoy) {
            return false;
        }

        return true;
    }
}