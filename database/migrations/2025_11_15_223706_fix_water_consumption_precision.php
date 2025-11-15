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
            // Cambiar el tipo de dato de water_consumption_m3 de integer a decimal para preservar decimales
            $table->decimal('water_consumption_m3', 10, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_expense_details', function (Blueprint $table) {
            // Revertir a integer (para rollback)
            $table->integer('water_consumption_m3')->change();
        });
    }
};
