<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Medidor extends Model
{
    protected $table = 'medidores';
    
    protected $fillable = [
        'numero_medidor',
        'ubicacion',
        'propiedad_id',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    /**
     * Relación con propiedad
     */
    public function propiedad(): BelongsTo
    {
        return $this->belongsTo(Propiedad::class);
    }

    /**
     * Relación con lecturas
     */
    public function lecturas(): HasMany
    {
        return $this->hasMany(Lectura::class);
    }

    /**
     * Relación con grupo de medidores (si es compartido)
     */
    public function grupoMedidor(): HasOne
    {
        return $this->hasOne(GrupoMedidor::class);
    }

    /**
     * Obtener última lectura
     */
    public function ultimaLectura()
    {
        return $this->lecturas()->latest('fecha_lectura')->first();
    }

    /**
     * Obtener lectura de un período específico
     */
    public function lecturaDelPeriodo(string $periodo)
    {
        return $this->lecturas()
            ->where('mes_periodo', $periodo)
            ->first();
    }

    /**
     * Scope para medidores activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener el tipo de medidor basado en el tipo de propiedad
     */
    public function getTipoAttribute(): string
    {
        if (!$this->propiedad || !$this->propiedad->tipoPropiedad) {
            return 'desconocido';
        }

        return $this->propiedad->tipoPropiedad->esComercial() 
            ? 'comercial' 
            : 'domiciliario';
    }

    /**
     * Verificar si es medidor compartido
     */
    public function esCompartido(): bool
    {
        return $this->grupoMedidor !== null;
    }

    /**
     * Scope para medidores domiciliarios
     */
    public function scopeDomiciliarios($query)
    {
        return $query->whereHas('propiedad.tipoPropiedad', function ($q) {
            $q->whereIn('nombre', ['Departamento', 'Casa']);
        });
    }

    /**
     * Scope para medidores comerciales
     */
    public function scopeComerciales($query)
    {
        return $query->whereHas('propiedad.tipoPropiedad', function ($q) {
            $q->whereIn('nombre', ['Local Comercial', 'Oficina']);
        });
    }

    /**
     * Scope para medidores individuales (no compartidos)
     */
    public function scopeIndividuales($query)
    {
        return $query->doesntHave('grupoMedidor');
    }

    /**
     * Scope para medidores compartidos
     */
    public function scopeCompartidos($query)
    {
        return $query->has('grupoMedidor');
    }
}