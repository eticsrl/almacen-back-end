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
    Schema::create('users_entities', function (Blueprint $table) {
        $table->id(); // Crea el ID como clave primaria

        // Claves foráneas
        $table->foreignId('users_id')->constrained('users');//->onDelete('cascade');
        //$table->foreignId('entities_id')->constrained('entities');//->onDelete('cascade');
// 1. Definir la columna manualmente como tinyInteger y unsigned
    $table->unsignedTinyInteger('entities_id');

    // 2. Definir la relación por separado
    $table->foreign('entities_id')->references('id')->on('entities');
        // Columnas adicionales
        $table->char('estado', 1)->default('V');
        $table->string('observaciones', 25)->nullable(); // nullable() es opcional, depende si es obligatorio

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_entities');
    }
};
