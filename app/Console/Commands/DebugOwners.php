<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugOwners extends Command
{
    protected $signature = 'app:debug-owners';
    protected $description = 'Debug property owners and test expense insertion';

    public function handle()
    {
        $this->info("=== INVESTIGACIÓN DE PROPIEDADES Y PROPIETARIOS ===");

        // Verificar propiedades y sus propietarios actuales
        $propiedades = DB::table('propiedades')->get();
        foreach ($propiedades as $prop) {
            $propietario = DB::table('propietario_propiedad')
                ->where('propiedad_id', $prop->id)
                ->whereNull('fecha_fin')
                ->first();

            if ($propietario) {
                $propietarioInfo = DB::table('propietarios')->where('id', $propietario->propietario_id)->first();
                $this->line("Propiedad {$prop->id} ({$prop->codigo}): Propietario {$propietario->propietario_id} (" . ($propietarioInfo->nombre_completo ?? 'N/A') . ")");
            } else {
                $this->line("Propiedad {$prop->id} ({$prop->codigo}): SIN PROPIETARIO ACTIVO");
            }
        }

        $this->info("\n=== VERIFICANDO PROPIEDAD 15 ===");
        $prop15Relations = DB::table('propietario_propiedad')->where('propiedad_id', 15)->get();
        foreach ($prop15Relations as $rel) {
            $this->line("Relación: Propietario {$rel->propietario_id}, Inicio: {$rel->fecha_inicio}, Fin: " . ($rel->fecha_fin ?? 'NULL'));
        }

        $this->info("\n=== PROPIETARIOS ACTUALES ===");
        $propietarios = DB::table('propietarios')->get();
        foreach ($propietarios as $propietario) {
            $propCount = DB::table('propietario_propiedad')
                ->where('propietario_id', $propietario->id)
                ->whereNull('fecha_fin')
                ->count();
            $this->line("Propietario {$propietario->id} ({$propietario->nombre_completo}): {$propCount} propiedades activas");
        }

        $this->info("\n=== INTENTANDO INSERTAR MANUALMENTE PARA VER EL ERROR ===");

        // Verificar si ya existe una expensa para propiedad 15, periodo 2
        $existente = DB::table('property_expenses')
            ->where('propiedad_id', 15)
            ->where('expense_period_id', 2)
            ->first();

        if ($existente) {
            $this->error("YA EXISTE una expensa para propiedad 15, período 2: ID {$existente->id}");
        } else {
            $this->info("No existe expensa para propiedad 15, período 2");

            // Intentar insertar una expensa manualmente para ver qué pasa
            try {
                DB::transaction(function () {
                    DB::table('property_expenses')->insert([
                        'expense_period_id' => 2,
                        'propiedad_id' => 15,
                        'propietario_id' => 1, // Suponiendo que el propietario 1 existe
                        'facturar_a' => 'propietario',
                        'base_amount' => 100,
                        'water_amount' => 50,
                        'other_amount' => 20,
                        'previous_debt' => 0,
                        'water_consumption' => 10.50,
                        'total_amount' => 170,
                        'paid_amount' => 0.00,
                        'balance' => 170,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                });
                $this->info("✅ INSERCIÓN MANUAL EXITOSA");
            } catch (\Exception $e) {
                $this->error("ERROR EN INSERCIÓN MANUAL: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}