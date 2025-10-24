<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medidor_id')
                ->constrained('medidores')
                ->onDelete('cascade');
            
            $table->decimal('lectura_actual', 8, 3)->unsigned();
            $table->decimal('lectura_anterior', 8, 3)->unsigned()->nullable();
            
            $table->decimal('consumo', 8, 3)
                ->virtualAs('lectura_actual - COALESCE(lectura_anterior, 0)')
                ->nullable();
            
            $table->date('fecha_lectura');
            $table->string('mes_periodo', 7); // "2024-01" (7 caracteres)
            
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->onDelete('restrict'); // No permitir eliminar usuario si tiene lecturas
            
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('fecha_lectura');
            $table->index('mes_periodo');
            
            $table->unique(['medidor_id', 'mes_periodo'], 'unique_lectura_mes');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};