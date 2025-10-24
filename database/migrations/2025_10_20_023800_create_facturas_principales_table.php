<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('facturas_principales', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('periodo_facturacion_id')
                ->constrained('periodos_facturacion')
                ->onDelete('cascade');
            
            // Tipo de factura
            $table->enum('tipo', ['comercial', 'domiciliario']);
            
            // Datos de la factura
            $table->string('numero_medidor_empresa', 50); // Ej: "00B16S002189"
            $table->decimal('importe_bs', 10, 2); // Monto en bolivianos
            $table->integer('consumo_m3')->unsigned(); // Metros cúbicos
            
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            
            // Control
            $table->foreignId('usuario_registro_id')
                ->constrained('users')
                ->onDelete('restrict');
            
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['periodo_facturacion_id', 'tipo']);
            
            // Evitar duplicados: solo 1 factura comercial y máximo 2 domiciliarias por período
            // Esto se validará en el modelo/controlador
        });
    }

    public function down()
    {
        Schema::dropIfExists('facturas_principales');
    }
};