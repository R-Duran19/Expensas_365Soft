<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugPropertyExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:debug-property-expenses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug property expenses table structure and data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Mostrar estructura de la tabla
            $schema = DB::select("SHOW CREATE TABLE property_expenses");
            $this->info("=== ESTRUCTURA DE LA TABLA property_expenses ===");
            $this->line($schema[0]->{'Create Table'});

            // Mostrar índices únicos
            $indexes = DB::select("SHOW INDEX FROM property_expenses WHERE Non_unique = 0");
            $this->info("\n=== ÍNDICES ÚNICOS ===");
            foreach ($indexes as $index) {
                $this->line("- {$index->Key_name}: {$index->Column_name}");
            }

            // Mostrar datos actuales
            $expenses = DB::table('property_expenses')->get();
            $this->info("\n=== DATOS ACTUALES EN property_expenses ===");
            $this->line("Total registros: " . $expenses->count());

            if ($expenses->count() > 0) {
                foreach ($expenses as $expense) {
                    $this->line("ID: {$expense->id}, Periodo: {$expense->expense_period_id}, Propiedad: {$expense->propiedad_id}, Monto: {$expense->total_amount}");
                }
            } else {
                $this->line("No hay registros en la tabla property_expenses");
            }

            // Verificar períodos
            $periodos = DB::table('expense_periods')->get();
            $this->info("\n=== PERÍODOS DISPONIBLES ===");
            foreach ($periodos as $period) {
                $count = DB::table('property_expenses')->where('expense_period_id', $period->id)->count();
                $this->line("- {$period->year}-{$period->month}: {$count} expensas");
            }

            // Verificar específicamente el conflicto
            $this->info("\n=== VERIFICACIÓN DE CONFLICTOS ===");
            $conflictCheck = DB::select("
                SELECT expense_period_id, propiedad_id, COUNT(*) as count
                FROM property_expenses
                GROUP BY expense_period_id, propiedad_id
                HAVING COUNT(*) > 1
            ");

            if (count($conflictCheck) > 0) {
                $this->error("¡HAY DUPLICADOS!");
                foreach ($conflictCheck as $conflict) {
                    $this->error("- Periodo {$conflict->expense_period_id}, Propiedad {$conflict->propiedad_id}: {$conflict->count} registros");
                }
            } else {
                $this->info("No hay duplicados en la tabla");
            }

            // Investigación específica de la propiedad 15
            $this->info("\n=== INVESTIGACIÓN PROPIEDAD 15 ===");
            $propiedad15 = DB::table('propiedades')->where('id', 15)->first();

            if ($propiedad15) {
                $this->info("Propiedad 15 encontrada: {$propiedad15->codigo}");

                // Verificar si tiene expensas en el período 2
                $conflictExpensa = DB::table('property_expenses')
                    ->where('propiedad_id', 15)
                    ->where('expense_period_id', 2)
                    ->first();

                if ($conflictExpensa) {
                    $this->error("❌ PROBLEMA: La propiedad 15 ya tiene una expensa en el período 2");
                    $this->error("  - ID: {$conflictExpensa->id}");
                    $this->error("  - Monto: {$conflictExpensa->total_amount}");
                    $this->error("  - Estado: {$conflictExpensa->status}");

                    // Verificar si esta expensa es del mismo propietario
                    $propietarioActual = DB::table('propietario_propiedad')
                        ->where('propiedad_id', 15)
                        ->whereNull('fecha_fin')
                        ->first();

                    if ($propietarioActual && $propietarioActual->propietario_id === $conflictExpensa->propietario_id) {
                        $this->error("  - Misma relación de propietario que la expensa existente");
                    }
                } else {
                    $this->info("✅ No hay conflicto directo con propiedad 15 en período 2");
                }
            } else {
                $this->error("❌ Propiedad 15 no encontrada en la tabla");
            }

        } catch (Exception $e) {
            $this->error("ERROR: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
