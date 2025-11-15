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
        Schema::table('property_expense_details', function (Blueprint $table) {
            // Relación con el factor de agua usado
            $table->foreignId('water_factor_id')->nullable()->after('id');

            // Cambiar las columnas de dinero a enteros (redondeados)
            $table->integer('base_amount')->change();
            $table->integer('water_amount')->change();
            $table->integer('total_amount')->change();

            // Cambiar el factor de agua para mayor precisión
            $table->decimal('factor_agua', 8, 6)->nullable()->change();

            // Asegurar que el consumo sea integer (m³ enteros)
            $table->integer('water_consumption_m3')->change();

            // Foreign key para water_factor_id
            $table->foreign('water_factor_id')
                ->references('id')
                ->on('water_factors')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_expense_details', function (Blueprint $table) {
            // Eliminar foreign key y columna
            $table->dropForeign(['water_factor_id']);
            $table->dropColumn('water_factor_id');

            // Revertir cambios en las columnas
            $table->decimal('base_amount', 8, 2)->change();
            $table->decimal('water_amount', 8, 2)->change();
            $table->decimal('total_amount', 8, 2)->change();
            $table->decimal('factor_agua', 4, 2)->nullable()->change();
            $table->decimal('water_consumption_m3', 10, 3)->change();
        });
    }
};