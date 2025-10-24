<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GrupoMedidor extends Model
{
    protected $table = 'grupos_medidores';
    
    protected $fillable = [
        'nombre',
        'medidor_id',
        'metodo_prorrateo',
        'activo',
        'observaciones'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    /**
     * Relaciones
     */
    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidor::class);
    }

    public function propiedades(): BelongsToMany
    {
        return $this->belongsToMany(
            Propiedad::class,
            'grupo_medidor_propiedad',
            'grupo_medidor_id',
            'propiedad_id'
        )->withPivot('porcentaje')->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Calcular distribución del consumo según método
     */
    public function calcularDistribucion(int $consumoTotal): array
    {
        $propiedades = $this->propiedades;
        $distribucion = [];

        switch ($this->metodo_prorrateo) {
            case 'partes_iguales':
                $cantidadPropiedades = $propiedades->count();
                if ($cantidadPropiedades == 0) break;
                
                $consumoPorPropiedad = $consumoTotal / $cantidadPropiedades;
                
                foreach ($propiedades as $propiedad) {
                    $distribucion[$propiedad->id] = round($consumoPorPropiedad, 2);
                }
                break;

            case 'por_m2':
                $m2Totales = $propiedades->sum('superficie_m2');
                if ($m2Totales == 0) break;
                
                foreach ($propiedades as $propiedad) {
                    $porcentaje = $propiedad->superficie_m2 / $m2Totales;
                    $distribucion[$propiedad->id] = round($consumoTotal * $porcentaje, 2);
                }
                break;

            case 'porcentaje_custom':
                $porcentajeTotal = $propiedades->sum('pivot.porcentaje');
                if ($porcentajeTotal == 0) break;
                
                foreach ($propiedades as $propiedad) {
                    $porcentaje = $propiedad->pivot->porcentaje / 100;
                    $distribucion[$propiedad->id] = round($consumoTotal * $porcentaje, 2);
                }
                break;
        }

        return $distribucion;
    }

    /**
     * Verificar si todos los porcentajes suman 100%
     */
    public function porcentajesValidos(): bool
    {
        if ($this->metodo_prorrateo !== 'porcentaje_custom') {
            return true;
        }

        $suma = $this->propiedades->sum('pivot.porcentaje');
        return abs($suma - 100) < 0.01; // Tolerancia de 0.01%
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        // Validar antes de guardar
        static::saving(function ($grupo) {
            if ($grupo->metodo_prorrateo === 'porcentaje_custom') {
                if (!$grupo->porcentajesValidos()) {
                    throw new \Exception('Los porcentajes deben sumar 100%');
                }
            }
        });
    }
}