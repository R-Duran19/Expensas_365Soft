<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Inquilino extends Model
{
    use SoftDeletes;

    protected $table = 'inquilinos';

    protected $fillable = [
        'nombre_completo',
        'ci',
        'telefono',
        'email',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Obtener todas las propiedades del inquilino
     */
    public function propiedades(): BelongsToMany
    {
        return $this->belongsToMany(Propiedad::class, 'inquilino_propiedad')
            ->withPivot([
                'fecha_inicio_contrato',
                'fecha_fin_contrato',
                'es_inquilino_principal',
                'observaciones'
            ])
            ->withTimestamps();
    }

    /**
     * Obtener solo las propiedades activas (sin fecha_fin o con fecha_fin futura)
     */
    public function propiedadesActivas(): BelongsToMany
    {
        return $this->propiedades()
            ->whereNull('inquilino_propiedad.fecha_fin_contrato')
            ->orWhere('inquilino_propiedad.fecha_fin_contrato', '>=', now());
    }

    /**
     * Obtener propiedades con contrato vigente
     */
    public function propiedadesContratoVigente(): BelongsToMany
    {
        return $this->propiedades()
            ->where('inquilino_propiedad.fecha_inicio_contrato', '<=', now())
            ->where(function ($query) {
                $query->whereNull('inquilino_propiedad.fecha_fin_contrato')
                      ->orWhere('inquilino_propiedad.fecha_fin_contrato', '>=', now());
            });
    }

    /**
     * Scope para inquilinos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para inquilinos con algún contrato vigente
     */
    public function scopeConContratoVigente($query)
    {
        return $query->whereHas('propiedades', function ($q) {
            $q->where('inquilino_propiedad.fecha_inicio_contrato', '<=', now())
              ->where(function ($subQ) {
                  $subQ->whereNull('inquilino_propiedad.fecha_fin_contrato')
                        ->orWhere('inquilino_propiedad.fecha_fin_contrato', '>=', now());
              });
        });
    }

    /**
     * Verificar si tiene algún contrato vigente
     */
    public function tieneContratoVigente(): bool
    {
        return $this->propiedadesContratoVigente()->count() > 0;
    }

    /**
     * Buscar inquilino por CI o nombre
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre_completo', 'like', "%{$termino}%")
              ->orWhere('ci', 'like', "%{$termino}%")
              ->orWhere('email', 'like', "%{$termino}%");
        });
    }
}