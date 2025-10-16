<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoPropiedad extends Model
{
    protected $table = 'tipos_propiedad';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Obtener todas las propiedades de este tipo
     */
    public function propiedades(): HasMany
    {
        return $this->hasMany(Propiedad::class, 'tipo_propiedad_id');
    }

    /**
     * Obtener todos los factores de cálculo asociados
     */
    public function factoresCalculo(): HasMany
    {
        return $this->hasMany(FactorCalculo::class, 'tipo_propiedad_id');
    }

    /**
     * Obtener el factor de cálculo activo actual
     */
    public function factorActivo()
    {
        return $this->hasOne(FactorCalculo::class, 'tipo_propiedad_id')
            ->where('activo', true)
            ->whereNull('fecha_fin')
            ->latest('fecha_inicio');
    }

    /**
     * Scope para tipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}