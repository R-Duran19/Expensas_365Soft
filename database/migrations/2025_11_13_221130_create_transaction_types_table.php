<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ingreso por Expensa, Egreso por Luz, etc.
            $table->string('code')->unique(); // ING_EXPENSA, EGR_LUZ
            $table->enum('type', ['income', 'expense']); // ingreso o egreso
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_types');
    }
};