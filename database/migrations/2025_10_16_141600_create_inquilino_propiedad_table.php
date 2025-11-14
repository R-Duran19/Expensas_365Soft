<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquilino_propiedad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('propiedad_id')->constrained('propiedades')->cascadeOnDelete();
            $table->date('fecha_inicio_contrato');
            $table->date('fecha_fin_contrato')->nullable();
            $table->boolean('es_inquilino_principal')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices y restricciones
            $table->unique(['inquilino_id', 'propiedad_id', 'fecha_inicio_contrato'], 'inq_prop_unique');
            $table->index(['propiedad_id', 'fecha_fin_contrato']);
            $table->index(['inquilino_id', 'fecha_fin_contrato']);

            // Restricción para evitar múltiples inquilinos principales en la misma propiedad
            $table->unique(['propiedad_id', 'fecha_fin_contrato'], 'prop_inq_principal_unique')
                  ->where('es_inquilino_principal', true)
                  ->whereNull('fecha_fin_contrato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquilino_propiedad');
    }
};