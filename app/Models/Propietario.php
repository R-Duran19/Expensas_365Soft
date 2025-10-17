<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Propietario extends Model
{
    protected $table = 'propietarios';

    protected $fillable = [
        'nombre_completo',
        'ci',
        'nit',
        'telefono',
        'email',
        'direccion_externa',
        'fecha_registro',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Obtener todas las propiedades del propietario
     */
    public function propiedades(): BelongsToMany
    {
        return $this->belongsToMany(Propiedad::class, 'propietario_propiedad')
            ->withPivot([
                'fecha_inicio',
                'fecha_fin',
                'es_propietario_principal',
                'observaciones'
            ])
            ->withTimestamps();
    }

    /**
     * Obtener solo las propiedades activas (sin fecha_fin)
     */
    public function propiedadesActivas(): BelongsToMany
    {
        return $this->propiedades()->whereNull('propietario_propiedad.fecha_fin');
    }

    /**
     * Scope para propietarios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Buscar propietario por CI o nombre
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