<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

try {
    echo "=== LIMPIEZA DEL PERÍODO 2 ===\n";

    // Verificar qué expensas hay en el período 2
    $expensesPeriodo2 = DB::table('property_expenses')->where('expense_period_id', 2)->get();

    echo "Expensas encontradas en período 2: " . $expensesPeriodo2->count() . "\n";

    if ($expensesPeriodo2->count() > 0) {
        foreach ($expensesPeriodo2 as $expense) {
            echo "- ID: {$expense->id}, Propiedad: {$expense->propiedad_id}, Monto: {$expense->total_amount}\n";
        }

        echo "\nEliminando expensas del período 2...\n";
        DB::table('property_expenses')->where('expense_period_id', 2)->delete();
        echo "✅ " . $expensesPeriodo2->count() . " expensas eliminadas del período 2.\n";

        // También eliminar detalles
        $detailsDeleted = DB::table('property_expense_details')
            ->whereExists(function($query) {
                $query->select(DB::raw(1))
                    ->from('property_expenses')
                    ->whereRaw('property_expenses.id = property_expense_details.property_expense_id')
                    ->where('property_expenses.expense_period_id', 2);
            })
            ->delete();

        echo "✅ {$detailsDeleted} detalles eliminados.\n";
    } else {
        echo "No hay expensas en el período 2 para limpiar.\n";
    }

    echo "\n=== VERIFICACIÓN FINAL ===\n";
    $remainingExpenses = DB::table('property_expenses')->where('expense_period_id', 2)->count();
    echo "Expensas restantes en período 2: {$remainingExpenses}\n";

    if ($remainingExpenses === 0) {
        echo "✅ El período 2 está limpio. Ahora puedes generar nuevas expensas.\n";
    } else {
        echo "⚠️  Hay {$remainingExpenses} expensas restantes. Puede haber un problema.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}