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
            // Eliminar el índice único duplicado que solo contiene propiedad_id
            // Este índice está causando conflictos con el índice compuesto correcto
            $table->dropUnique('property_expenses_expense_period_id_propiedad_id_unique');

            // Recrear el índice único compuesto correcto (expense_period_id, propiedad_id)
            $table->unique(['expense_period_id', 'propiedad_id'], 'property_expenses_expense_period_id_propiedad_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_expenses', function (Blueprint $table) {
            // En caso de rollback, eliminar el índice compuesto
            $table->dropUnique('property_expenses_expense_period_id_propiedad_id_unique');
        });
    }
};
