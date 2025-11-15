<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

            // Nueva relación con period_id en lugar de mes_periodo
            $table->foreignId('period_id')
                ->constrained('expense_periods')
                ->onDelete('restrict');

            // Mantener mes_periodo temporalmente para compatibilidad y migración de datos
            $table->string('mes_periodo', 7)->nullable(); // "2024-01" (7 caracteres)

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->onDelete('restrict'); // No permitir eliminar usuario si tiene lecturas

            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('fecha_lectura');
            $table->index('mes_periodo');
            $table->index('period_id');

            // Unique constraint actualizado para usar period_id
            $table->unique(['medidor_id', 'period_id'], 'unique_lectura_period');

            // Mantener el antiguo por compatibilidad temporal
            $table->unique(['medidor_id', 'mes_periodo'], 'unique_lectura_mes');
        });

        // Migrar datos existentes de mes_periodo a period_id (solo si hay datos)
        if (Schema::hasTable('expense_periods')) {
            DB::statement('
                UPDATE lecturas l
                SET period_id = (
                    SELECT id FROM expense_periods ep
                    WHERE CONCAT(ep.year, "-", LPAD(ep.month, 2, "0")) = l.mes_periodo
                    LIMIT 1
                )
                WHERE mes_periodo IS NOT NULL AND period_id IS NULL
            ');
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('lecturas');
    }
};