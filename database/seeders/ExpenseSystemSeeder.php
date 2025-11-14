<?php

namespace Database\Seeders;

use App\Models\ExpenseConcept;
use App\Models\PaymentType;
use App\Models\TransactionType;
use Illuminate\Database\Seeder;

class ExpenseSystemSeeder extends Seeder
{
    public function run(): void
    {
        // Conceptos de Expensa
        ExpenseConcept::create([
            'name' => 'Expensa Base',
            'code' => 'EXP_BASE',
            'description' => 'Expensa mensual calculada por m²',
            'is_active' => true,
            'order' => 1,
        ]);

        ExpenseConcept::create([
            'name' => 'Consumo de Agua',
            'code' => 'AGUA',
            'description' => 'Consumo mensual de agua según lectura de medidor',
            'is_active' => true,
            'order' => 2,
        ]);

        ExpenseConcept::create([
            'name' => 'Extraordinaria',
            'code' => 'EXTRA',
            'description' => 'Gastos extraordinarios',
            'is_active' => true,
            'order' => 3,
        ]);

        // Tipos de Pago
        PaymentType::create([
            'name' => 'Efectivo',
            'code' => 'CASH',
            'is_active' => true,
        ]);

        PaymentType::create([
            'name' => 'Transferencia Bancaria',
            'code' => 'TRANSFER',
            'is_active' => true,
        ]);

        PaymentType::create([
            'name' => 'QR',
            'code' => 'QR',
            'is_active' => true,
        ]);

        // Tipos de Transacción
        TransactionType::create([
            'name' => 'Ingreso por Expensa',
            'code' => 'ING_EXPENSA',
            'type' => 'income',
            'is_active' => true,
        ]);

        TransactionType::create([
            'name' => 'Ingreso Extraordinario',
            'code' => 'ING_EXTRA',
            'type' => 'income',
            'is_active' => true,
        ]);

        TransactionType::create([
            'name' => 'Egreso por Servicios',
            'code' => 'EGR_SERVICIOS',
            'type' => 'expense',
            'is_active' => true,
        ]);

        TransactionType::create([
            'name' => 'Egreso por Mantenimiento',
            'code' => 'EGR_MANT',
            'type' => 'expense',
            'is_active' => true,
        ]);
    }
}