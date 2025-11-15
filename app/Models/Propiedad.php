<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Propiedad extends Model
{
    use SoftDeletes;

    protected $table = 'propiedades';

    protected $fillable = [
        'codigo',
        'tipo_propiedad_id',
        'ubicacion',
        'metros_cuadrados',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'metros_cuadrados' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * Obtener el tipo de propiedad
     */
    public function tipoPropiedad(): BelongsTo
    {
        return $this->belongsTo(TipoPropiedad::class, 'tipo_propiedad_id');
    }

    /**
     * Obtener todos los propietarios de esta propiedad
     */
    public function propietarios(): BelongsToMany
    {
        return $this->belongsToMany(Propietario::class, 'propietario_propiedad')
            ->withPivot([
                'fecha_inicio',
                'fecha_fin',
                'es_propietario_principal',
                'observaciones'
            ])
            ->withTimestamps();
    }

    /**
     * Obtener solo propietarios activos
     */
    public function propietariosActivos(): BelongsToMany
    {
        return $this->propietarios()->whereNull('propietario_propiedad.fecha_fin');
    }

    /**
     * Obtener el propietario principal
     */
    public function propietarioPrincipal()
    {
        return $this->belongsToMany(Propietario::class, 'propietario_propiedad')
            ->wherePivot('es_propietario_principal', true)
            ->whereNull('propietario_propiedad.fecha_fin')
            ->withPivot([
                'fecha_inicio',
                'observaciones'
            ])
            ->first();
    }

    /**
     * Obtener el inquilino activo actual (simplificado)
     * Por ahora, solo verificamos si hay inquilinos activos en el sistema
     */
    public function inquilinoActivo(): HasOne
    {
        // Simplificado - solo devuelve inquilinos activos
        return $this->hasOne(Inquilino::class)
            ->where('activo', true);
    }

    /**
     * Calcular el monto base (m² × factor)
     */
    public function calcularMontoBase()
    {
        $factor = $this->tipoPropiedad->factorActivo->factor ?? 0;
        return $this->metros_cuadrados * $factor;
    }

    /**
     * Scope para propiedades activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por código o ubicación
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', "%{$termino}%")
                ->orWhere('ubicacion', 'like', "%{$termino}%");
        });
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopePorTipo($query, $tipoId)
    {
        return $query->where('tipo_propiedad_id', $tipoId);
    }
    
    public function medidor(): HasOne
    {
        return $this->hasOne(Medidor::class);
    }

    /**
     * Verificar si la propiedad requiere medidor según su tipo
     */
    public function requiereMedidor(): bool
    {
        return $this->tipoPropiedad && $this->tipoPropiedad->requiere_medidor;
    }

    /**
     * Verificar si tiene medidor asignado
     */
    public function tieneMedidor(): bool
    {
        return $this->medidor()->exists();
    }

    /**
     * Scope para propiedades que requieren medidor
     */
    public function scopeRequierenMedidor($query)
    {
        return $query->whereHas('tipoPropiedad', function ($q) {
            $q->where('requiere_medidor', true);
        });
    }

    /**
     * Scope para propiedades sin medidor asignado
     */
    public function scopeSinMedidor($query)
    {
        return $query->doesntHave('medidor');
    }
}