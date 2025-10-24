<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('periodos_facturacion', function (Blueprint $table) {
            $table->id();
            $table->string('mes_periodo', 7)->unique(); // "2025-01"
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            
            // Estado del período
            $table->enum('estado', ['abierto', 'cerrado', 'calculado'])
                ->default('abierto');
            
            // Factores calculados (se llenan automáticamente)
            $table->decimal('factor_comercial', 10, 4)->nullable();
            $table->decimal('factor_domiciliario', 10, 4)->nullable();
            
            // Control
            $table->foreignId('usuario_creacion_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->foreignId('usuario_cierre_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');
            $table->timestamp('fecha_cierre')->nullable();
            
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index('estado');
            $table->index('mes_periodo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('periodos_facturacion');
    }
};