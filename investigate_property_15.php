<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

try {
    echo "=== INVESTIGACIÓN DE PROPIEDAD 15 ===\n";

    // 1. Verificar si la propiedad 15 existe
    $propiedad15 = DB::table('propiedades')->where('id', 15)->first();

    if (!$propiedad15) {
        echo "❌ PROPIEDAD 15 NO EXISTE EN LA TABLA propiedades\n";
        exit;
    }

    echo "✅ Propiedad 15 encontrada:\n";
    echo "- ID: {$propiedad15->id}\n";
    echo "- Código: {$propiedad15->codigo}\n";
    echo "- Ubicación: {$propiedad->ubicacion}\n";
    echo "- Tipo: {$propiedad15->tipo_propiedad_id}\n";
    echo "- Estado: " . ($propiedad15->activo ? 'Activo' : 'Inactivo') . "\n\n";

    // 2. Verificar si tiene propietario activo
    $propietarioActivo = DB::table('propietario_propiedad')
        ->where('propiedad_id', 15)
        ->whereNull('fecha_fin')
        ->first();

    if (!$propietarioActivo) {
        echo "❌ PROPIEDAD 15 NO TIENE PROPIETARIO ACTIVO\n";
        echo "Esto podría causar problemas en la generación de expensas.\n";
    } else {
        echo "✅ Propietario activo: ID {$propietarioActivo->propietario_id}\n\n";

        // Verificar datos del propietario
        $propietario = DB::table('propietarios')->where('id', $propietarioActivo->propietario_id)->first();
        if ($propietario) {
            echo "  - Nombre: {$propietario->nombre_completo}\n";
            echo "  - ID: {$propietario->id}\n";
        }
    }

    // 3. Verificar relaciones conflictivas
    echo "\n=== VERIFICACIÓN DE RELACIONES ===\n";

    // Verificar si la propiedad tiene conflictos
    $conflictos = DB::table('propietario_propiedad')
        ->where('propiedad_id', 15)
        ->whereNotNull('fecha_fin')
        ->get();

    if ($conflictos->count() > 0) {
        echo "⚠️  ADVERTENCIA: Hay relaciones antiguas sin cerrar\n";
        foreach ($conflictos as $conflicto) {
            echo "  - Propietario {$conflicto->propietario_id} (terminó el {$conflicto->fecha_fin})\n";
        }
    }

    // 4. Verificar expensas existentes
    $expensasPropiedad15 = DB::table('property_expenses')->where('propiedad_id', 15)->get();
    echo "\n=== EXPENSAS EXISTENTES ===\n";
    echo "Total expensas para propiedad 15: " . $expensasPropiedad15->count() . "\n";

    foreach ($expensasPropiedad15 as $exp) {
        $periodo = DB::table('expense_periods')->where('id', $exp->expense_period_id)->first();
        echo "- Período {$periodo->year}-{$periodo->month} (ID: {$exp->expense_period_id}): Bs {$exp->total_amount}\n";
    }

    // 5. Verificar específicamente si hay alguna en el período 2
    $expensasPeriodo2 = DB::table('property_expenses')
        ->where('propiedad_id', 15)
        ->where('expense_period_id', 2)
        ->get();

    echo "\n=== EXPENSAS EN PERÍODO 2 ===\n";
    if ($expensasPeriodo2->count() > 0) {
        echo "❌ ¡CONFLICTO! Hay {$expensasPeriodo2->count()} expensas para propiedad 15 en el período 2\n";
        foreach ($expensasPeriodo2 as $exp) {
            echo "  - ID: {$exp->id}, Monto: {$exp->total_amount}, Estado: {$exp->status}\n";
        }
    } else {
        echo "✅ No hay expensas para propiedad 15 en el período 2\n";
    }

    // 6. Verificar si el problema está en la creación o validación
    echo "\n=== ANÁLISIS DEL ERROR ===\n";
    echo "El error 'Duplicate entry 2-15' significa que:\n";
    echo "- Se intenta crear una expensa con (periodo_id=2, propiedad_id=15)\n";
    echo "- Pero ya existe una con esa combinación\n";
    echo "- El problema ocurre DURANTE la transacción de inserción\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}