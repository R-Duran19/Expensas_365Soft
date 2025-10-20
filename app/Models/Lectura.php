<?php

// app/Models/Lectura.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'fecha_lectura' => 'date'
    ];

    // Relación con medidor
    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidor::class);
    }

    // Relación con usuario que registró
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Calcular consumo automáticamente
    public function getConsumoAttribute()
    {
        return $this->lectura_actual - ($this->lectura_anterior ?? 0);
    }

    // Obtener lectura anterior del mismo medidor
    public function lecturaPrevia()
    {
        return self::where('medidor_id', $this->medidor_id)
            ->where('id', '<', $this->id)
            ->latest()
            ->first();
    }
}