<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('expensas', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('periodo_facturacion_id')
                ->constrained('periodos_facturacion')
                ->onDelete('cascade');
            
            $table->foreignId('propiedad_id')
                ->constrained('propiedades')
                ->onDelete('cascade');
            
            // Datos de consumo de agua
            $table->integer('consumo_m3')->unsigned()->default(0);
            $table->decimal('monto_agua_bs', 10, 2)->default(0);
            
            // Factor utilizado (guardado por historial)
            $table->decimal('factor_aplicado', 10, 4)->nullable();
            
            // Monto total
            $table->decimal('monto_total_bs', 10, 2);
            
            // Estado de pago
            $table->enum('estado_pago', ['pendiente', 'pagado_parcial', 'pagado', 'vencido'])
                ->default('pendiente');
            
            $table->decimal('monto_pagado_bs', 10, 2)->default(0);
            $table->decimal('saldo_bs', 10, 2)->default(0);
            
            // Fechas
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            
            // Control
            $table->foreignId('usuario_generacion_id')
                ->constrained('users')
                ->onDelete('restrict');
            
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('estado_pago');
            $table->index(['periodo_facturacion_id', 'propiedad_id']);
            
            // Una propiedad solo puede tener una expensa por período
            $table->unique(['periodo_facturacion_id', 'propiedad_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('expensas');
    }
};