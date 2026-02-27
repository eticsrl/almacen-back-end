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
        Schema::create('discharge_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('egreso_id');
            $table->unsignedInteger('ingreso_detalle_id');
            $table->unsignedInteger('receta_item_id')->nullable()->index();
            $table->unsignedInteger('cantidad_solicitada');
            $table->decimal('costo_unitario',15,4);
            $table->decimal('costo_total',15,4);
            $table->string('observaciones')->nullable();
            $table->integer('usr');
            $table->integer('estado_id');

            $table->timestamps();

            $table->foreign ('egreso_id')->references('id')->on('discharges');
            $table->foreign ('ingreso_detalle_id')->references('id')->on('entry_details');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discharge_details');
    }
};
