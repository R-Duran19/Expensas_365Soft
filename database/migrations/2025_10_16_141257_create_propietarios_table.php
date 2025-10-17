<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propietarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('ci', 20)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('direccion_externa')->nullable();
            $table->date('fecha_registro')->default(now());
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para búsquedas
            $table->index('ci');
            $table->index('email');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propietarios');
    }
};