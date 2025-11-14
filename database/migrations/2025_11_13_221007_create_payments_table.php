<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique(); // Número de recibo
            $table->foreignId('propiedad_id')->constrained('propiedades')->onDelete('cascade');
            $table->foreignId('propietario_id')->constrained('propietarios')->onDelete('cascade');
            $table->foreignId('inquilino_id')->nullable()->constrained('inquilinos')->nullOnDelete();
            $table->enum('pagado_por', ['propietario', 'inquilino'])->default('propietario');
            $table->foreignId('payment_type_id')->constrained()->onDelete('cascade');
            
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->timestamp('registered_at')->useCurrent();
            
            $table->string('reference')->nullable(); // Número de transferencia, etc.
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'cancelled'])->default('active');
            
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};