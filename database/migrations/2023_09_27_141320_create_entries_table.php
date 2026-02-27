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
        Schema::create('entries', function (Blueprint $table) {

            $table->increments('id');
            $table->unsignedTinyInteger('entity_id')->index();
            $table->unsignedInteger('tipo_documento_id');
            $table->integer('numero')->default(0);
            $table->datetime('fecha_ingreso');
            $table->unsignedInteger('proveedor_id')->nullable();
            $table->integer('num_factura')->nullable();
            $table->string('observaciones');
            $table->integer('usr');
            $table->integer('estado_id')->index();
            $table->integer('usr_mod')->nullable();
            $table->datetime('fhr_mod')->nullable();

            $table->timestamps();

            $table->foreign ('tipo_documento_id')->references('id')->on('document_types');
            $table->foreign('entity_id')->references('id')->on('entities');
            $table->foreign ('proveedor_id')->references('id')->on('suppliers');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('entries');
        Schema::enableForeignKeyConstraints();
    }
};
