<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TiposPropiedadSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'nombre' => 'Parqueo',
                'descripcion' => 'Espacios de estacionamiento',
                'requiere_medidor' => false,
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nombre' => 'Baulera',
                'descripcion' => 'Espacios de almacenamiento o depósito',
                'requiere_medidor' => false,
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nombre' => 'Local',
                'descripcion' => 'Locales comerciales',
                'requiere_medidor' => true,
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nombre' => 'Oficina',
                'descripcion' => 'Oficinas',
                'requiere_medidor' => true,
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nombre' => 'Departamento',
                'descripcion' => 'Departamentos residenciales',
                'requiere_medidor' => true,
                'activo' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('tipos_propiedad')->insert($tipos);
    }
}
