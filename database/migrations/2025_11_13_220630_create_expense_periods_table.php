<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('year'); // 2025
            $table->integer('month'); // 1-12
            $table->date('period_date'); // 2025-11-01
            $table->enum('status', ['open', 'closed', 'cancelled'])->default('open');
            $table->decimal('total_generated', 12, 2)->default(0); // Total generado en expensas
            $table->decimal('total_collected', 12, 2)->default(0); // Total cobrado
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_periods');
    }
};