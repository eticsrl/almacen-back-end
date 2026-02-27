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

 Schema::create('discharges', function (Blueprint $table) {
            $table->increments('id');
            $table->datetime('fecha_egreso');
            $table->unsignedTinyInteger('entity_id');
            $table->unsignedInteger('tipo_documento_id');
            $table->integer('numero');
            $table->unsignedBigInteger('receta_id')->nullable();
            $table->unsignedInteger('servicio_id')->nullable();
            $table->unsignedInteger('proveedor_id')->nullable();
            $table->string('observaciones');
            $table->integer('usr');
            $table->integer('estado_id');
            $table->integer('usr_mod')->nullable();
            $table->datetime('fhr_mod')->nullable();

            $table->timestamps();


            $table->foreign('entity_id')->references('id')->on('entities');
            $table->foreign ('tipo_documento_id')->references('id')->on('document_types');

            $table->unique(
                ['entity_id', 'tipo_documento_id', 'numero'],
                'uk_discharges_numero'
            );

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discharges');
    }
};
