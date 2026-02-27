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
        Schema::create('suppliers', function (Blueprint $table) {

            $table->increments('id');
            $table->string('nombre');
            $table->string('nit');
            $table->string('direccion');
            $table->string('telefono');
            $table->string('persona_contacto');
            $table->string('celular');
            $table->string('email');
            $table->text('observaciones');
            $table->integer('usr');
            $table->integer('estado_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
