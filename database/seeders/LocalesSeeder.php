<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LocalesSeeder extends Seeder
{
    public function run(): void
    {
        $locales = [
            // PLANTA 1
            ['codigo' => 'LOC-1', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 18.25],
            ['codigo' => 'LOC-2', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 19.92],
            ['codigo' => 'LOC-3', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 29.92],
            ['codigo' => 'LOC-4', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 28.84],
            ['codigo' => 'LOC-5', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 28.82],
            ['codigo' => 'LOC-6', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 30.77],
            ['codigo' => 'LOC-7', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 21.13],
            ['codigo' => 'LOC-8', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 19.37],
            ['codigo' => 'LOC-9', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 21.44],
            ['codigo' => 'LOC-10', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 23.35],
            ['codigo' => 'LOC-11', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 1', 'metros_cuadrados' => 19.78],

            // PLANTA 2
            ['codigo' => 'LOC-12', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 2', 'metros_cuadrados' => 100.05],
            ['codigo' => 'LOC-14', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 2', 'metros_cuadrados' => 27.66],
            ['codigo' => 'LOC-15', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 2', 'metros_cuadrados' => 51.61],
            ['codigo' => 'LOC-16', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 2', 'metros_cuadrados' => 19.26],
            ['codigo' => 'LOC-17', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 2', 'metros_cuadrados' => 20.09],
            ['codigo' => 'LOC-18', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 2', 'metros_cuadrados' => 38.21],
            ['codigo' => 'LOC-19', 'tipo_propiedad_id' => 3, 'ubicacion' => 'Planta 2', 'metros_cuadrados' => 23.54],
        ];

        foreach ($locales as $local) {
            DB::table('propiedades')->insert([
                'codigo' => $local['codigo'],
                'tipo_propiedad_id' => $local['tipo_propiedad_id'],
                'ubicacion' => $local['ubicacion'],
                'metros_cuadrados' => $local['metros_cuadrados'],
                'activo' => true,
                'observaciones' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}