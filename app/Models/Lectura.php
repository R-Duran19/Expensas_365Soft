<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Lectura extends Model
{
    protected $table = 'lecturas';
    
    protected $fillable = [
        'medidor_id',
        'lectura_actual',
        'lectura_anterior',
        'fecha_lectura',
        'mes_periodo',
        'usuario_id',
        'observaciones'
        // 'consumo' NO va aquí porque es columna virtual
    ];

    protected $casts = [
        'fecha_lectura' => 'date',
        'lectura_actual' => 'decimal:3',
        'lectura_anterior' => 'decimal:3',
        'consumo' => 'decimal:3'
    ];

    protected $appends = ['consumo_m3']; // Para mostrar en m³

    /**
     * Relación con medidor
     */
    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidor::class);
    }

    /**
     * Relación con usuario que registró
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación con período de facturación
     */
    public function periodoFacturacion(): BelongsTo
    {
        return $this->belongsTo(PeriodoFacturacion::class, 'mes_periodo', 'mes_periodo');
    }

    /**
     * Accessor: Consumo en m³ (asumiendo que lectura está en litros)
     * Si tus lecturas ya están en m³, puedes omitir esta conversión
     */
    public function getConsumoM3Attribute(): float
    {
        // Si el consumo ya está en m³, retorna directo:
        return $this->consumo;
        
        // Si está en litros, convierte:
        // return round($this->consumo / 1000, 2);
    }

    /**
     * Obtener lectura anterior del mismo medidor
     */
    public function lecturaPrevia()
    {
        return self::where('medidor_id', $this->medidor_id)
            ->where('fecha_lectura', '<', $this->fecha_lectura)
            ->orderBy('fecha_lectura', 'desc')
            ->first();
    }

    /**
     * Obtener siguiente lectura del mismo medidor
     */
    public function lecturaSiguiente()
    {
        return self::where('medidor_id', $this->medidor_id)
            ->where('fecha_lectura', '>', $this->fecha_lectura)
            ->orderBy('fecha_lectura', 'asc')
            ->first();
    }

    /**
     * Verificar si es la última lectura del medidor
     */
    public function esUltimaLectura(): bool
    {
        return $this->id === $this->medidor->ultimaLectura()->id;
    }

    /**
     * Verificar si puede ser eliminada
     */
    public function puedeSerEliminada(): bool
    {
        // Solo se puede eliminar si es la última lectura
        return $this->esUltimaLectura();
    }

    /**
     * Scope: lecturas de un período específico
     */
    public function scopeDelPeriodo($query, string $periodo)
    {
        return $query->where('mes_periodo', $periodo);
    }

    /**
     * Scope: lecturas de un rango de fechas
     */
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_lectura', [$desde, $hasta]);
    }

    /**
     * Scope: lecturas con alto consumo (para alertas)
     */
    public function scopeConsumoAlto($query, int $umbral = 50)
    {
        return $query->whereRaw('(lectura_actual - COALESCE(lectura_anterior, 0)) > ?', [$umbral]);
    }

    /**
     * Formatear período para mostrar (2024-01 → Enero 2024)
     */
    public function getPeriodoFormateadoAttribute(): string
    {
        try {
            $fecha = Carbon::createFromFormat('Y-m', $this->mes_periodo);
            return $fecha->locale('es')->isoFormat('MMMM YYYY');
        } catch (\Exception $e) {
            return $this->mes_periodo;
        }
    }

    /**
     * Boot: eventos del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de eliminar, verificar que sea posible
        static::deleting(function ($lectura) {
            if (!$lectura->puedeSerEliminada()) {
                throw new \Exception(
                    'Solo se puede eliminar la última lectura del medidor. ' .
                    'Existen lecturas posteriores que dependen de ésta.'
                );
            }
        });
    }
}