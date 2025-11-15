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
        Schema::table('property_expenses', function (Blueprint $table) {
            // Cambiar las columnas de dinero a enteros (redondeados)
            $table->integer('base_amount')->change();
            $table->integer('water_amount')->change();
            $table->integer('other_amount')->change();
            $table->integer('previous_debt')->change();
            $table->integer('total_amount')->change();
            $table->integer('balance')->change();

            // Agregar relación con water_factors para referencia
            $table->foreignId('water_factor_id')->nullable()->after('expense_period_id');

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
        Schema::table('property_expenses', function (Blueprint $table) {
            // Eliminar foreign key y columna
            $table->dropForeign(['water_factor_id']);
            $table->dropColumn('water_factor_id');

            // Revertir cambios en las columnas
            $table->decimal('base_amount', 10, 2)->change();
            $table->decimal('water_amount', 10, 2)->change();
            $table->decimal('other_amount', 10, 2)->change();
            $table->decimal('previous_debt', 10, 2)->change();
            $table->decimal('total_amount', 12, 2)->change();
            $table->decimal('balance', 12, 2)->change();
        });
    }
};