<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // public function up()
    // {
    //     Schema::create('pagos', function (Blueprint $table) {
    //         $table->id();
            
    //         $table->foreignId('expensa_id')
    //             ->constrained('expensas')
    //             ->onDelete('cascade');
            
    //         $table->decimal('monto_bs', 10, 2);
    //         $table->date('fecha_pago');
            
    //         $table->enum('metodo_pago', [
    //             'efectivo',
    //             'transferencia',
    //             'deposito',
    //             'cheque',
    //             'qr',
    //             'otro'
    //         ])->default('efectivo');
            
    //         $table->string('numero_comprobante', 100)->nullable();
    //         $table->string('referencia', 200)->nullable();
            
    //         // Control
    //         $table->foreignId('usuario_registro_id')
    //             ->constrained('users')
    //             ->onDelete('restrict');
            
    //         $table->text('observaciones')->nullable();
    //         $table->timestamps();
            
    //         $table->index('fecha_pago');
    //         $table->index('expensa_id');
    //     });
    // }

    // public function down()
    // {
    //     Schema::dropIfExists('pagos');
    // }
};