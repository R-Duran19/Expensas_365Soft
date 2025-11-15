<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanPeriod extends Command
{
    protected $signature = 'app:clean-period {period_id}';
    protected $description = 'Limpiar expensas de un período específico';

    public function handle()
    {
        $periodId = $this->argument('period_id');

        try {
            $this->info("=== LIMPIEZA DEL PERÍODO {$periodId} ===");

            // Verificar qué expensas hay
            $expenses = DB::table('property_expenses')
                ->join('propiedades', 'propiedades.id', '=', 'property_expenses.propiedad_id')
                ->join('propietarios', 'propietarios.id', '=', 'property_expenses.propietario_id')
                ->where('property_expenses.expense_period_id', $periodId)
                ->select(
                    'property_expenses.*',
                    'propiedades.codigo as propiedad_codigo',
                    'propietarios.nombre_completo as propietario_nombre'
                )
                ->get();

            $this->info("Expensas encontradas: " . $expenses->count());

            if ($expenses->count() > 0) {
                foreach ($expenses as $expense) {
                    $this->line("- ID: {$expense->id}, Propiedad: {$expense->propiedad_codigo}, Propietario: {$expense->propietario_nombre}, Monto: {$expense->total_amount}");
                }

                if ($this->confirm("¿Desea eliminar estas {$expenses->count()} expensas?")) {
                    DB::transaction(function () use ($periodId) {
                        // Eliminar detalles primero
                        DB::table('property_expense_details')
                            ->whereExists(function($query) use ($periodId) {
                                $query->select(DB::raw(1))
                                    ->from('property_expenses')
                                    ->whereRaw('property_expenses.id = property_expense_details.property_expense_id')
                                    ->where('property_expenses.expense_period_id', $periodId);
                            })
                            ->delete();

                        // Eliminar expensas principales
                        DB::table('property_expenses')
                            ->where('expense_period_id', $periodId)
                            ->delete();
                    });

                    $this->info("✅ {$expenses->count()} expensas eliminadas del período {$periodId}");
                }
            } else {
                $this->info("No hay expensas en el período {$periodId}");
            }

            // Verificación final
            $remaining = DB::table('property_expenses')->where('expense_period_id', $periodId)->count();
            $this->info("Expensas restantes en período {$periodId}: {$remaining}");

            if ($remaining === 0) {
                $this->info("✅ Período {$periodId} limpio. Ahora puedes generar nuevas expensas.");
            }

        } catch (Exception $e) {
            $this->error("ERROR: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}