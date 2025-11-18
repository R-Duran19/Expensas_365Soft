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
        Schema::table('payments', function (Blueprint $table) {
            // Agregar campo para asociar el pago a un período específico
            $table->foreignId('expense_period_id')
                  ->nullable()
                  ->after('payment_type_id')
                  ->constrained('expense_periods')
                  ->onDelete('set null');

            // Agregar índice para búsquedas rápidas
            $table->index(['propietario_id', 'expense_period_id'], 'payments_owner_period_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeignIdFor('expense_periods', 'expense_period_id');
            $table->dropIndex('payments_owner_period_index');
        });
    }
};
