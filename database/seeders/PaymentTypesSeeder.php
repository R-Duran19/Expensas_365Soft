<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use App\Models\TransactionType;
use Illuminate\Database\Seeder;

class PaymentTypesSeeder extends Seeder
{
    public function run(): void
    {
        // Tipos de Pago
        $paymentTypes = [
            ['name' => 'Efectivo', 'code' => 'CASH'],
            ['name' => 'Transferencia Bancaria', 'code' => 'TRANSFER'],
            ['name' => 'QR', 'code' => 'QR'],
            ['name' => 'Otro...', 'code' => 'otro'],
        ];

        foreach ($paymentTypes as $type) {
            PaymentType::firstOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'is_active' => true,
                ]
            );
        }

        // Tipos de Transacción
        $transactionTypes = [
            ['name' => 'Ingreso por Expensa', 'code' => 'ING_EXPENSA', 'type' => 'income'],
            ['name' => 'Ingreso Extraordinario', 'code' => 'ING_EXTRA', 'type' => 'income'],
            ['name' => 'Egreso por Servicios', 'code' => 'EGR_SERVICIOS', 'type' => 'expense'],
            ['name' => 'Egreso por Mantenimiento', 'code' => 'EGR_MANT', 'type' => 'expense'],
            ['name' => 'Devolución de Pago', 'code' => 'REFUND_PAYMENT', 'type' => 'expense'],
        ];

        foreach ($transactionTypes as $type) {
            TransactionType::firstOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'type' => $type['type'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Payment types and transaction types seeded successfully!');
    }
}