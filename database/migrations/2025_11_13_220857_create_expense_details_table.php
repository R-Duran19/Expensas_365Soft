<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_expense_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_concept_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Para guardar info extra como m², factor, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_details');
    }
};