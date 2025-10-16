<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FactoresCalculoSeeder extends Seeder
{
    public function run(): void
    {
        $factores = [
            [
                'tipo_propiedad_id' => 1, // Parqueo
                'factor' => 2.10,
                'fecha_inicio' => Carbon::create(2018, 8, 13),
                'fecha_fin' => null,
                'activo' => true,
                'observaciones' => 'Factor para espacios de estacionamiento',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tipo_propiedad_id' => 2, // Baulera
                'factor' => 2.10,
                'fecha_inicio' => Carbon::create(2018, 8, 13),
                'fecha_fin' => null,
                'activo' => true,
                'observaciones' => 'Factor para espacios de depósito',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tipo_propiedad_id' => 3, // Local
                'factor' => 3.50,
                'fecha_inicio' => Carbon::create(2018, 8, 13),
                'fecha_fin' => null,
                'activo' => true,
                'observaciones' => 'Factor para locales comerciales',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tipo_propiedad_id' => 4, // Oficina
                'factor' => 3.50,
                'fecha_inicio' => Carbon::create(2018, 8, 13),
                'fecha_fin' => null,
                'activo' => true,
                'observaciones' => 'Factor para oficinas',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'tipo_propiedad_id' => 5, // Departamento
                'factor' => 2.10,
                'fecha_inicio' => Carbon::create(2018, 8, 13),
                'fecha_fin' => null,
                'activo' => true,
                'observaciones' => 'Factor para departamentos residenciales',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('factores_calculo')->insert($factores);
    }
}