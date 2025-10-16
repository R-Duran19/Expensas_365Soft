<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartamentosSeeder extends Seeder
{
    public function run(): void
    {
        $departamentos = [
            // PLANTA 11
            ['codigo' => 'DEP-11-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 11', 'metros_cuadrados' => 100.64],
            ['codigo' => 'DEP-11-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 11', 'metros_cuadrados' => 100.63],
            ['codigo' => 'DEP-11-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 11', 'metros_cuadrados' => 89.28],
            ['codigo' => 'DEP-11-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 11', 'metros_cuadrados' => 89.22],

            // PLANTA 12
            ['codigo' => 'DEP-12-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 12', 'metros_cuadrados' => 100.46],
            ['codigo' => 'DEP-12-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 12', 'metros_cuadrados' => 100.46],
            ['codigo' => 'DEP-12-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 12', 'metros_cuadrados' => 91.09],
            ['codigo' => 'DEP-12-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 12', 'metros_cuadrados' => 91.09],

            // PLANTA 13
            ['codigo' => 'DEP-13-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 13', 'metros_cuadrados' => 154.03],
            ['codigo' => 'DEP-13-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 13', 'metros_cuadrados' => 153.95],
            ['codigo' => 'DEP-13-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 13', 'metros_cuadrados' => 42.07],
            ['codigo' => 'DEP-13-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 13', 'metros_cuadrados' => 41.94],

            // PLANTA 14
            ['codigo' => 'DEP-14-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 14', 'metros_cuadrados' => 154.03],
            ['codigo' => 'DEP-14-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 14', 'metros_cuadrados' => 153.95],
            ['codigo' => 'DEP-14-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 14', 'metros_cuadrados' => 42.07],
            ['codigo' => 'DEP-14-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 14', 'metros_cuadrados' => 41.94],

            // PLANTA 15
            ['codigo' => 'DEP-15-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 15', 'metros_cuadrados' => 153.87],
            ['codigo' => 'DEP-15-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 15', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-15-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 15', 'metros_cuadrados' => 47.92],
            ['codigo' => 'DEP-15-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 15', 'metros_cuadrados' => 41.95],

            // PLANTA 16
            ['codigo' => 'DEP-16-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 16', 'metros_cuadrados' => 154.03],
            ['codigo' => 'DEP-16-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 16', 'metros_cuadrados' => 153.95],
            ['codigo' => 'DEP-16-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 16', 'metros_cuadrados' => 42.07],
            ['codigo' => 'DEP-16-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 16', 'metros_cuadrados' => 41.94],

            // PLANTA 17
            ['codigo' => 'DEP-17-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 17', 'metros_cuadrados' => 153.87],
            ['codigo' => 'DEP-17-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 17', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-17-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 17', 'metros_cuadrados' => 47.92],
            ['codigo' => 'DEP-17-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 17', 'metros_cuadrados' => 41.95],

            // PLANTA 18
            ['codigo' => 'DEP-18-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 18', 'metros_cuadrados' => 153.87],
            ['codigo' => 'DEP-18-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 18', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-18-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 18', 'metros_cuadrados' => 47.92],
            ['codigo' => 'DEP-18-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 18', 'metros_cuadrados' => 41.95],

            // PLANTA 19
            ['codigo' => 'DEP-19-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 19', 'metros_cuadrados' => 148.04],
            ['codigo' => 'DEP-19-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 19', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-19-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 19', 'metros_cuadrados' => 47.92],
            ['codigo' => 'DEP-19-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 19', 'metros_cuadrados' => 47.78],

            // PLANTA 20
            ['codigo' => 'DEP-20-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 20', 'metros_cuadrados' => 153.87],
            ['codigo' => 'DEP-20-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 20', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-20-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 20', 'metros_cuadrados' => 47.92],
            ['codigo' => 'DEP-20-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 20', 'metros_cuadrados' => 41.95],

            // PLANTA 21
            ['codigo' => 'DEP-21-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 21', 'metros_cuadrados' => 148.04],
            ['codigo' => 'DEP-21-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 21', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-21-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 21', 'metros_cuadrados' => 47.92],
            ['codigo' => 'DEP-21-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 21', 'metros_cuadrados' => 47.78],

            // PLANTA 22
            ['codigo' => 'DEP-22-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 22', 'metros_cuadrados' => 153.87],
            ['codigo' => 'DEP-22-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 22', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-22-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 22', 'metros_cuadrados' => 47.92],
            ['codigo' => 'DEP-22-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 22', 'metros_cuadrados' => 41.95],

            // PLANTA 23
            ['codigo' => 'DEP-23-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 23', 'metros_cuadrados' => 148.04],
            ['codigo' => 'DEP-23-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 23', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-23-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 23', 'metros_cuadrados' => 47.92],
            ['codigo' => 'DEP-23-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 23', 'metros_cuadrados' => 47.78],

            // PLANTA 24
            ['codigo' => 'DEP-24-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 24', 'metros_cuadrados' => 154.01],
            ['codigo' => 'DEP-24-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 24', 'metros_cuadrados' => 153.93],
            ['codigo' => 'DEP-24-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 24', 'metros_cuadrados' => 42.05],
            ['codigo' => 'DEP-24-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 24', 'metros_cuadrados' => 41.92],

            // PLANTA 25
            ['codigo' => 'DEP-25-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 25', 'metros_cuadrados' => 148.04],
            ['codigo' => 'DEP-25-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 25', 'metros_cuadrados' => 147.97],
            ['codigo' => 'DEP-25-C-T', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 25 Terraza', 'metros_cuadrados' => 108.92],
            ['codigo' => 'DEP-25-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 25', 'metros_cuadrados' => 47.78],

            // PLANTA 26
            ['codigo' => 'DEP-26-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 26', 'metros_cuadrados' => 100.46],
            ['codigo' => 'DEP-26-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 26', 'metros_cuadrados' => 100.46],
            ['codigo' => 'DEP-26-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 26', 'metros_cuadrados' => 91.09],
            ['codigo' => 'DEP-26-D', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 26', 'metros_cuadrados' => 91.09],

            // PLANTA 27
            ['codigo' => 'DEP-27-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 27', 'metros_cuadrados' => 210.88],
            ['codigo' => 'DEP-27-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 27', 'metros_cuadrados' => 91.19],
            ['codigo' => 'DEP-27-C', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 27', 'metros_cuadrados' => 90.81],

            // PLANTA 28-29 (Departamentos duplex)
            ['codigo' => 'DEP-28-29-A', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 28-29', 'metros_cuadrados' => 260.96],
            ['codigo' => 'DEP-28-29-B', 'tipo_propiedad_id' => 5, 'ubicacion' => 'Planta 28-29', 'metros_cuadrados' => 254.76],
        ];

        foreach ($departamentos as $departamento) {
            DB::table('propiedades')->insert([
                'codigo' => $departamento['codigo'],
                'tipo_propiedad_id' => $departamento['tipo_propiedad_id'],
                'ubicacion' => $departamento['ubicacion'],
                'metros_cuadrados' => $departamento['metros_cuadrados'],
                'activo' => true,
                'observaciones' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}