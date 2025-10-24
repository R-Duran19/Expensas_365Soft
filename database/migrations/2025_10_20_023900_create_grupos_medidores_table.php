<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grupos_medidores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: "Oficinas 3A-3D", "Locales 14-17"
            
            $table->foreignId('medidor_id')
                ->constrained('medidores')
                ->onDelete('cascade');
            
            // Método de prorrateo
            $table->enum('metodo_prorrateo', ['partes_iguales', 'por_m2', 'porcentaje_custom'])
                ->default('partes_iguales');
            
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // Tabla pivot: propiedades que pertenecen a cada grupo
        Schema::create('grupo_medidor_propiedad', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('grupo_medidor_id')
                ->constrained('grupos_medidores')
                ->onDelete('cascade');
            
            $table->foreignId('propiedad_id')
                ->constrained('propiedades')
                ->onDelete('cascade');
            
            // Para prorrateo personalizado
            $table->decimal('porcentaje', 5, 2)->nullable(); // 0.00 a 100.00
            
            $table->timestamps();
            
            // Una propiedad no puede estar en el mismo grupo 2 veces
            $table->unique(['grupo_medidor_id', 'propiedad_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('grupo_medidor_propiedad');
        Schema::dropIfExists('grupos_medidores');
    }
};