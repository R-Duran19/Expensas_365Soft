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
            $table->foreign('expense_period_id')
                ->references('id')
                ->on('expense_periods')
                ->onDelete('cascade');

            $table->foreign('propiedad_id')
                ->references('id')
                ->on('propiedades')
                ->onDelete('cascade');

            $table->foreign('propietario_id')
                ->references('id')
                ->on('propietarios')
                ->onDelete('cascade');

            $table->foreign('inquilino_id')
                ->references('id')
                ->on('inquilinos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_period_id']);
            $table->dropForeign(['propiedad_id']);
            $table->dropForeign(['propietario_id']);
            $table->dropForeign(['inquilino_id']);
        });
    }
};