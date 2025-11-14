<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('propiedad_id')->constrained('propiedades')->onDelete('cascade');
            $table->foreignId('propietario_id')->constrained('propietarios')->onDelete('cascade');
            $table->foreignId('inquilino_id')->nullable()->constrained('inquilinos')->nullOnDelete();
            $table->enum('facturar_a', ['propietario', 'inquilino'])->default('propietario');
            
            // Montos
            $table->decimal('base_amount', 10, 2)->default(0); // Expensa base
            $table->decimal('water_amount', 10, 2)->default(0); // Consumo de agua
            $table->decimal('other_amount', 10, 2)->default(0); // Otros conceptos
            $table->decimal('previous_debt', 10, 2)->default(0); // Deuda arrastrada
            $table->decimal('total_amount', 10, 2)->default(0); // Total a pagar
            $table->decimal('paid_amount', 10, 2)->default(0); // Pagado
            $table->decimal('balance', 10, 2)->default(0); // Saldo pendiente
            
            // Datos para cálculo de agua
            $table->decimal('water_previous_reading', 10, 2)->nullable();
            $table->decimal('water_current_reading', 10, 2)->nullable();
            $table->decimal('water_consumption', 10, 2)->nullable(); // m³
            $table->decimal('water_factor', 10, 4)->nullable(); // Factor aplicado
            
            // Estados
            $table->enum('status', ['pending', 'partial', 'paid', 'cancelled'])->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['expense_period_id', 'propiedad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_expenses');
    }
};