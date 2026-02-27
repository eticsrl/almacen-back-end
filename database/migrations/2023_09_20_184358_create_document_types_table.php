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
        Schema::create('document_types', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('categoria_id');
            $table->string('descripcion');
            $table->integer('cod_servicio')->default(0);;
            $table->integer('usr');
            $table->integer('estado_id');
            $table->timestamps();

            $table->foreign ('categoria_id')->references('id')->on('categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
