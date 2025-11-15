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
        'period_id',
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
     * Relación con período de facturación (nueva relación)
     */
    public function expensePeriod(): BelongsTo
    {
        return $this->belongsTo(ExpensePeriod::class, 'period_id');
    }

    /**
     * Relación con período de facturación (mantener compatibilidad)
     * @deprecated Usar expensePeriod() en su lugar
     */
    public function periodoFacturacion(): BelongsTo
    {
        return $this->belongsTo(ExpensePeriod::class, 'period_id');
    }

    /**
     * Obtener período formateado (nuevo método)
     */
    public function getPeriodoFormateadoAttribute(): string
    {
        if ($this->expensePeriod) {
            return $this->expensePeriod->getPeriodName();
        }

        // Fallback al método antiguo para compatibilidad
        try {
            $fecha = Carbon::createFromFormat('Y-m', $this->mes_periodo);
            return $fecha->locale('es')->isoFormat('MMMM YYYY');
        } catch (\Exception $e) {
            return $this->mes_periodo ?? 'Período desconocido';
        }
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
     * Scope: lecturas de un período específico (nuevo método)
     */
    public function scopeDelPeriodo($query, $periodo)
    {
        // Si es un ID numérico, buscar por period_id
        if (is_numeric($periodo)) {
            return $query->where('period_id', $periodo);
        }

        // Si es formato string (2025-11), mantener compatibilidad
        return $query->where('mes_periodo', $periodo);
    }

    /**
     * Scope: lecturas de un período específico por ID
     */
    public function scopeDelPeriodoId($query, int $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /**
     * Scope: lecturas de un año específico
     */
    public function scopeDelAnio($query, int $anio)
    {
        return $query->whereHas('expensePeriod', function($q) use ($anio) {
            $q->where('year', $anio);
        });
    }

    /**
     * Scope: lecturas de un mes específico
     */
    public function scopeDelMes($query, int $mes)
    {
        return $query->whereHas('expensePeriod', function($q) use ($mes) {
            $q->where('month', $mes);
        });
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