<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_propiedad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique(); // Departamento, Oficina, Local, Parqueo, Baulera
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_propiedad');
    }
};