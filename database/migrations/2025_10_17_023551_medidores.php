<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medidores', function (Blueprint $table) {
            $table->id();
            $table->string('numero_medidor')->unique();
            $table->string('ubicacion')->nullable(); // "Piso 1 - Departamento A"
            $table->foreignId('propiedad_id')
                  ->unique() // UNA propiedad = UN medidor
                  ->constrained('propiedades')
                  ->onDelete('cascade');
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medidores');
    }
};