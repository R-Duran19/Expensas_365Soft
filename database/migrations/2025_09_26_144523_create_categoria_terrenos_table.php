<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categorias_terrenos', function (Blueprint $table) {
            $table->id(); // id autoincrementable y primary key
            $table->string('nombre'); // nombre requerido
            $table->unsignedBigInteger('idproyecto'); // idproyecto como foreign key
            $table->boolean('estado')->default(true); // estado activo/inactivo
            $table->timestamps();

            // Definimos la llave foránea
            $table->foreign('idproyecto')->references('id')->on('proyectos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_terrenos');
    }
};
