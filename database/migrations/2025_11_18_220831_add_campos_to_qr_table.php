<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qr', function (Blueprint $table) {
            $table->after('estado', function (Blueprint $table) {
                $table->foreignId('pago_id')->nullable()->constrained('pagos')->onDelete('cascade');
                $table->decimal('monto', 10, 2)->nullable();
                $table->foreignId('propietario_id')->nullable()->constrained('propietarios')->onDelete('cascade');
                $table->foreignId('expensa_id')->nullable()->constrained('expensas')->onDelete('cascade');
                $table->json('imagen_qr')->nullable(); // Para guardar la imagen base64 y otros datos del QR
                $table->datetime('fecha_vencimiento')->nullable();
                $table->string('detalle_glosa', 255)->nullable();

                $table->index(['pago_id', 'estado']);
                $table->index(['alias', 'estado']);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qr', function (Blueprint $table) {
            $table->dropForeign(['pago_id']);
            $table->dropForeign(['propietario_id']);
            $table->dropForeign(['expensa_id']);
            $table->dropColumn([
                'pago_id',
                'monto',
                'propietario_id',
                'expensa_id',
                'imagen_qr',
                'fecha_vencimiento',
                'detalle_glosa'
            ]);
            $table->dropIndex(['pago_id', 'estado']);
            $table->dropIndex(['alias', 'estado']);
        });
    }
};
