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
        Schema::create('medicines', function (Blueprint $table) {
            $table->increments ('id');
            $table->string('liname');
            $table->unsignedInteger('categoriamed_id');
            $table->string('nombre_generico');
            $table->unsignedTinyInteger('formafarmaceutica_id');
            $table->string('observaciones');
            $table->integer('stockmax');
            $table->integer('stockmin');
            $table->integer('darmax');
            $table->integer('darmin');
            $table->unsignedTinyInteger('estado_id');
            $table->unsignedTinyInteger('usr');

            $table->timestamps();


            $table->foreign ('categoriamed_id')->references('id')->on('document_types');
            $table->foreign ('formafarmaceutica_id')->references('id')->on('pharmaceutical_forms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('medicines');
        Schema::enableForeignKeyConstraints();
    }
};
