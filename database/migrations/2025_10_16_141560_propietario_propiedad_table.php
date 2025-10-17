<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propietario_propiedad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propietario_id')->constrained('propietarios')->cascadeOnDelete();
            $table->foreignId('propiedad_id')->constrained('propiedades')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('es_propietario_principal')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices y restricciones
            $table->unique(['propietario_id', 'propiedad_id', 'fecha_inicio'], 'prop_prop_unique');
            $table->index(['propiedad_id', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propietario_propiedad');
    }
};
