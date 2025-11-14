<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null'); // Si es ingreso por pago
            
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 10, 2);
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};