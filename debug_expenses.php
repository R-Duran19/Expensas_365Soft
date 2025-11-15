<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

try {
    // Mostrar estructura de la tabla
    $schema = DB::select("SHOW CREATE TABLE property_expenses");
    echo "=== ESTRUCTURA DE LA TABLA property_expenses ===\n";
    echo $schema[0]->{'Create Table'} . "\n\n";

    // Mostrar índices únicos
    $indexes = DB::select("SHOW INDEX FROM property_expenses WHERE Non_unique = 0");
    echo "=== ÍNDICES ÚNICOS ===\n";
    foreach ($indexes as $index) {
        echo "- {$index->Key_name}: {$index->Column_name}\n";
    }
    echo "\n";

    // Mostrar datos actuales
    $expenses = DB::table('property_expenses')->get();
    echo "=== DATOS ACTUALES EN property_expenses ===\n";
    echo "Total registros: " . $expenses->count() . "\n";

    foreach ($expenses as $expense) {
        echo "ID: {$expense->id}, Periodo: {$expense->expense_period_id}, Propiedad: {$expense->propiedad_id}, Monto: {$expense->total_amount}\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}