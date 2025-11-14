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

            // Relaciones principales (sin foreign keys por ahora)
            $table->unsignedBigInteger('expense_period_id');

            $table->unsignedBigInteger('propiedad_id');

            $table->unsignedBigInteger('propietario_id');

            // Facturación a propietario o inquilino
            $table->unsignedBigInteger('inquilino_id')->nullable();

            $table->enum('facturar_a', ['propietario', 'inquilino'])
                ->default('propietario');

            // Desglose de montos
            $table->decimal('base_amount', 10, 2)->default(0);
            $table->decimal('water_amount', 10, 2)->default(0);
            $table->decimal('other_amount', 10, 2)->default(0);
            $table->decimal('previous_debt', 10, 2)->default(0);

            // Agua
            $table->decimal('water_consumption', 10, 2)->default(0);
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
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Notas
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index('status');
            $table->index(['expense_period_id', 'propiedad_id']);

            // Una propiedad solo puede tener una expensa por periodo
            $table->unique(['expense_period_id', 'propiedad_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_expenses');
    }

    /**
     * Add foreign keys after all tables exist
     * This should be called manually after all migrations are done
     */
    public function upForeignKeys()
    {
        Schema::table('property_expenses', function (Blueprint $table) {
            $table->foreign('expense_period_id')
                ->references('id')
                ->on('expense_periods')
                ->onDelete('cascade');

            $table->foreign('propiedad_id')
                ->references('id')
                ->on('propiedades')
                ->onDelete('cascade');

            $table->foreign('propietario_id')
                ->references('id')
                ->on('propietarios')
                ->onDelete('cascade');

            $table->foreign('inquilino_id')
                ->references('id')
                ->on('inquilinos')
                ->nullOnDelete();
        });
    }
};
