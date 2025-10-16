<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propiedades', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique(); // "125-118", "114", "Departamento A"
            $table->foreignId('tipo_propiedad_id')->constrained('tipos_propiedad');
            $table->string('ubicacion'); // "Sotano 4", "Planta 11"
            $table->decimal('metros_cuadrados', 10, 2);
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('codigo');
            $table->index('tipo_propiedad_id');
            $table->index('ubicacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propiedades');
    }
};