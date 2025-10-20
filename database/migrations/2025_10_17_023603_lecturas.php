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
            $table->foreignId('medidor_id')->constrained('lecturas')->onDelete('cascade');
            $table->integer('lectura_actual');
            $table->integer('lectura_anterior')->nullable();
            $table->integer('consumo')->virtualAs('lectura_actual - COALESCE(lectura_anterior, 0)');
            $table->date('fecha_lectura');
            $table->string('mes_periodo'); // Ej: "2024-01"
            $table->foreignId('usuario_id')->constrained('users'); // Quién registró
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Evitar duplicados por mes
            $table->unique(['medidor_id', 'mes_periodo']);
        });
    }
    public function down(): void
    {
        //
    }
};
