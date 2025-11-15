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
        Schema::create('water_factors', function (Blueprint $table) {
            $table->id();

            // Relación con el período de facturación
            $table->foreignId('expense_period_id')
                ->constrained('expense_periods')
                ->onDelete('cascade');

            // Factores de agua con alta precisión
            $table->decimal('factor_comercial', 8, 6)->nullable();
            $table->decimal('factor_domiciliario', 8, 6)->nullable();

            // Datos de origen para auditoría
            $table->decimal('total_consumo_comercial', 10, 3)->default(0);
            $table->decimal('total_importe_comercial', 12, 2)->default(0);
            $table->decimal('total_consumo_domiciliario', 10, 3)->default(0);
            $table->decimal('total_importe_domiciliario', 12, 2)->default(0);

            // Auditoría
            $table->unsignedBigInteger('usuario_calculo_id')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índice único por período
            $table->unique('expense_period_id', 'unique_water_factor_period');

            // Índices para rendimiento
            $table->index('factor_comercial');
            $table->index('factor_domiciliario');
        });

        // Agregar foreign key para usuario_calculo_id
        Schema::table('water_factors', function (Blueprint $table) {
            $table->foreign('usuario_calculo_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_factors');
    }
};