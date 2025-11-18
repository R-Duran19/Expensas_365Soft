<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Propiedad;
use App\Models\Medidor;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MedidoresSeeder extends Seeder
{
    public function run(): void
    {
        // Propiedades activas que requieren medidor y no lo tienen
        $propiedades = Propiedad::activas()
            ->requierenMedidor()
            ->sinMedidor()
            ->get();

        if ($propiedades->isEmpty()) {
            $this->command->info('No hay propiedades sin medidor.');
            return;
        }

        $now = Carbon::now();

        foreach ($propiedades as $propiedad) {
            Medidor::create([
                'numero_medidor' => $this->generarNumeroMedidorUnico(),
                'propiedad_id' => $propiedad->id,
                'ubicacion' => null,
                'activo' => true,
                'observaciones' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info("Se generaron {$propiedades->count()} medidores.");
    }

    private function generarNumeroMedidorUnico(): string
    {
        do {
            $numero = Str::upper(Str::random(7));
        } while (Medidor::where('numero_medidor', $numero)->exists());

        return $numero;
    }
}
