<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('facturas_medidores_principales', function (Blueprint $table) {
            $table->id();

            // Relación con período de facturación
            $table->string('mes_periodo', 7); // "2025-11"

            // Información del medidor
            $table->string('numero_medidor', 50);
            $table->enum('tipo', ['comercial', 'domiciliario']);

            // Datos de la factura
            $table->decimal('importe_bs', 12, 2);
            $table->decimal('consumo_m3', 10, 3);
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            // Cálculo automático del factor
            $table->decimal('factor_calculado', 8, 4)->nullable();

            // Auditoría
            $table->unsignedBigInteger('usuario_registro_id');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices
            $table->index('mes_periodo');
            $table->index('tipo');
            $table->unique(['mes_periodo', 'numero_medidor'], 'unique_factura_medidor_periodo');

            // Foreign keys (se agregan después)
        });
    }

    public function down()
    {
        Schema::dropIfExists('facturas_medidores_principales');
    }

    /**
     * Add foreign keys after all tables exist
     */
    public function upForeignKeys()
    {
        Schema::table('facturas_medidores_principales', function (Blueprint $table) {
            $table->foreign('usuario_registro_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }
};