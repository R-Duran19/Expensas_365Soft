<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_expense_details', function (Blueprint $table) {
            $table->id();

            // Relación con la expensa consolidada
            $table->foreignId('property_expense_id')->constrained()->onDelete('cascade');

            // Información de la propiedad individual
            $table->foreignId('propiedad_id')->constrained('propiedades');
            $table->string('propiedad_codigo');
            $table->string('propiedad_ubicacion');
            $table->decimal('metros_cuadrados', 10, 2);
            $table->string('tipo_propiedad');

            // Factores de cálculo
            $table->decimal('factor_expensas', 8, 2); // Factor para expensas comunes
            $table->decimal('factor_agua', 8, 2); // Factor para agua
            $table->decimal('factor_calculado', 8, 2); // Factor real utilizado

            // Montos individuales
            $table->decimal('base_amount', 12, 2)->default(0); // m² × factor_expensas
            $table->decimal('water_amount', 12, 2)->default(0); // consumo × factor_agua
            $table->decimal('total_amount', 12, 2)->default(0); // base + water

            // Información de agua (si aplica)
            $table->integer('water_consumption_m3')->nullable(); // Consumo en m³
            $table->decimal('water_previous_reading', 10, 2)->nullable();
            $table->decimal('water_current_reading', 10, 2)->nullable();
            $table->string('water_medidor_codigo')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['property_expense_id', 'propiedad_id']);
            $table->index('propiedad_codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_expense_details');
    }
};