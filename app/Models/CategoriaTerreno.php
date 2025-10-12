<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaTerreno extends Model
{
    use HasFactory;

    // Tabla asociada (opcional si sigue la convención)
    protected $table = 'categorias_terrenos';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'idproyecto',
        'estado',
    ];

    // Relación con Proyecto
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto', 'id');
    }
}