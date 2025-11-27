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
            // Add columns only if they don't exist
            if (!Schema::hasColumn('qr', 'pago_id')) {
                $table->foreignId('pago_id')->nullable()->after('estado')->constrained('payments')->onDelete('cascade');
            }
            if (!Schema::hasColumn('qr', 'monto')) {
                $table->decimal('monto', 10, 2)->nullable()->after('pago_id');
            }
            if (!Schema::hasColumn('qr', 'propietario_id')) {
                $table->foreignId('propietario_id')->nullable()->after('monto')->constrained('propietarios')->onDelete('cascade');
            }
            if (!Schema::hasColumn('qr', 'expensa_id')) {
                $table->foreignId('expensa_id')->nullable()->after('propietario_id')->constrained('property_expenses')->onDelete('cascade');
            }
            if (!Schema::hasColumn('qr', 'imagen_qr')) {
                $table->json('imagen_qr')->nullable()->after('expensa_id'); // Para guardar la imagen base64 y otros datos del QR
            }
            if (!Schema::hasColumn('qr', 'fecha_vencimiento')) {
                $table->datetime('fecha_vencimiento')->nullable()->after('imagen_qr');
            }
            if (!Schema::hasColumn('qr', 'detalle_glosa')) {
                $table->string('detalle_glosa', 255)->nullable()->after('fecha_vencimiento');
            }

            // Add indexes only if they don't exist
            if (!Schema::hasIndex('qr', ['pago_id', 'estado'])) {
                $table->index(['pago_id', 'estado']);
            }
            if (!Schema::hasIndex('qr', ['alias', 'estado'])) {
                $table->index(['alias', 'estado']);
            }
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
