<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factores_calculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_propiedad_id')->constrained('tipos_propiedad')->cascadeOnDelete();
            $table->decimal('factor', 8, 2); // 2.1, 3.5, etc.
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable(); // Si se cambia el factor, se cierra el anterior
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índice compuesto para búsquedas eficientes
            $table->index(['tipo_propiedad_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factores_calculo');
    }
};