<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TiposPropiedadSeeder::class,
            FactoresCalculoSeeder::class,
            PaymentTypesSeeder::class,
            ParqueosBaulerasSeeder::class,
            LocalesSeeder::class,
            OficinasSeeder::class,
            DepartamentosSeeder::class,
            ExpenseSystemSeeder::class,
        ]);
    }
}
