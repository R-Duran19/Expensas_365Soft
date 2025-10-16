<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OficinasSeeder extends Seeder
{
    public function run(): void
    {
        $oficinas = [
            // PLANTA 3
            ['codigo' => 'OF-3-A', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 3', 'metros_cuadrados' => 50.36],
            ['codigo' => 'OF-3-B', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 3', 'metros_cuadrados' => 50.26],
            ['codigo' => 'OF-3-C', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 3', 'metros_cuadrados' => 54.14],
            ['codigo' => 'OF-3-D', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 3', 'metros_cuadrados' => 52.59],

            // PLANTA 4
            ['codigo' => 'OF-4-A', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 4', 'metros_cuadrados' => 52.57],
            ['codigo' => 'OF-4-B', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 4', 'metros_cuadrados' => 100.66],
            ['codigo' => 'OF-4-C', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 4', 'metros_cuadrados' => 46.95],

            // PLANTA 5
            ['codigo' => 'OF-5-A', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 5', 'metros_cuadrados' => 52.56],
            ['codigo' => 'OF-5-B', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 5', 'metros_cuadrados' => 44.96],
            ['codigo' => 'OF-5-C', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 5', 'metros_cuadrados' => 52.56],
            ['codigo' => 'OF-5-D', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 5', 'metros_cuadrados' => 51.59],
            ['codigo' => 'OF-5-E', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 5', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-5-F', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 5', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-5-G', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 5', 'metros_cuadrados' => 52.88],

            // PLANTA 6
            ['codigo' => 'OF-6-A', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 6', 'metros_cuadrados' => 52.46],
            ['codigo' => 'OF-6-B', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 6', 'metros_cuadrados' => 44.96],
            ['codigo' => 'OF-6-C', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 6', 'metros_cuadrados' => 52.56],
            ['codigo' => 'OF-6-D', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 6', 'metros_cuadrados' => 51.59],
            ['codigo' => 'OF-6-E', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 6', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-6-F', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 6', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-6-G', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 6', 'metros_cuadrados' => 52.88],

            // PLANTA 7
            ['codigo' => 'OF-7-A', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 7', 'metros_cuadrados' => 52.46],
            ['codigo' => 'OF-7-B', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 7', 'metros_cuadrados' => 44.96],
            ['codigo' => 'OF-7-C', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 7', 'metros_cuadrados' => 52.56],
            ['codigo' => 'OF-7-D', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 7', 'metros_cuadrados' => 51.59],
            ['codigo' => 'OF-7-E', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 7', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-7-F', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 7', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-7-G', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 7', 'metros_cuadrados' => 52.88],

            // PLANTA 8
            ['codigo' => 'OF-8-A', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 8', 'metros_cuadrados' => 52.46],
            ['codigo' => 'OF-8-B', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 8', 'metros_cuadrados' => 44.96],
            ['codigo' => 'OF-8-C', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 8', 'metros_cuadrados' => 52.56],
            ['codigo' => 'OF-8-D', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 8', 'metros_cuadrados' => 51.59],
            ['codigo' => 'OF-8-E', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 8', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-8-F', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 8', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-8-G', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 8', 'metros_cuadrados' => 52.88],

            // PLANTA 9
            ['codigo' => 'OF-9-A', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 9', 'metros_cuadrados' => 52.46],
            ['codigo' => 'OF-9-B', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 9', 'metros_cuadrados' => 44.96],
            ['codigo' => 'OF-9-C', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 9', 'metros_cuadrados' => 52.56],
            ['codigo' => 'OF-9-D', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 9', 'metros_cuadrados' => 51.59],
            ['codigo' => 'OF-9-E', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 9', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-9-F', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 9', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-9-G', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 9', 'metros_cuadrados' => 52.88],

            // PLANTA 10
            ['codigo' => 'OF-10-A', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 10', 'metros_cuadrados' => 52.46],
            ['codigo' => 'OF-10-B', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 10', 'metros_cuadrados' => 42.54],
            ['codigo' => 'OF-10-C', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 10', 'metros_cuadrados' => 52.56],
            ['codigo' => 'OF-10-D', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 10', 'metros_cuadrados' => 51.59],
            ['codigo' => 'OF-10-E', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 10', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-10-F', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 10', 'metros_cuadrados' => 40.47],
            ['codigo' => 'OF-10-G', 'tipo_propiedad_id' => 4, 'ubicacion' => 'Planta 10', 'metros_cuadrados' => 52.88],
        ];

        foreach ($oficinas as $oficina) {
            DB::table('propiedades')->insert([
                'codigo' => $oficina['codigo'],
                'tipo_propiedad_id' => $oficina['tipo_propiedad_id'],
                'ubicacion' => $oficina['ubicacion'],
                'metros_cuadrados' => $oficina['metros_cuadrados'],
                'activo' => true,
                'observaciones' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}