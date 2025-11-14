<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_expenses', function (Blueprint $table) {
            $table->id();

            // Relaciones principales
            $table->foreignId('periodo_facturacion_id')
                ->constrained('periodos_facturacion')
                ->onDelete('cascade');

            $table->foreignId('propiedad_id')
                ->constrained('propiedades')
                ->onDelete('cascade');

            // Facturación a propietario o inquilino
            $table->foreignId('inquilino_id')
                ->nullable()
                ->constrained('inquilinos')
                ->nullOnDelete();

            $table->enum('facturar_a', ['propietario', 'inquilino'])
                ->default('propietario');

            // Desglose de montos
            $table->decimal('base_amount', 10, 2)->default(0);
            $table->decimal('other_amount', 10, 2)->default(0);
            $table->decimal('previous_debt', 10, 2)->default(0);

            // Agua
            $table->integer('water_consumption')->unsigned()->default(0);
            $table->decimal('water_amount', 10, 2)->default(0);
            $table->decimal('water_factor', 10, 4)->nullable();

            // Lecturas históricas de agua
            $table->decimal('water_previous_reading', 10, 2)->nullable();
            $table->decimal('water_current_reading', 10, 2)->nullable();

            // Totales
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);

            // Estado
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])
                ->default('pending');

            // Fechas
            $table->date('issued_at');
            $table->date('due_date');

            // Control
            $table->foreignId('usuario_generacion_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices
            $table->index('status');
            $table->index(['periodo_facturacion_id', 'propiedad_id']);

            // Una propiedad solo puede tener una expensa por periodo
            $table->unique(['periodo_facturacion_id', 'propiedad_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_expenses');
    }
};
