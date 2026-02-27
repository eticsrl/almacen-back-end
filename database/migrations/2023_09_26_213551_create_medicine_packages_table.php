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
        Schema::create('medicine_packages', function (Blueprint $table) {

            $table->increments('id');
            $table->unsignedInteger('documento_id');
            $table->unsignedInteger('medicamento_id');
            $table->integer('cantidad');
            $table->string('observaciones');
            $table->integer('dias');
            $table->integer('usr');
            $table->integer('estado_id');
            $table->timestamps();

            $table->foreign ('documento_id')->references('id')->on('document_types');
            $table->foreign('medicamento_id')->references('id')->on('medicines');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_packages');
    }
};
