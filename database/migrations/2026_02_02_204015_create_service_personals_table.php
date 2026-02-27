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
        Schema::create('service_personals', function (Blueprint $table) {
            $table->id(); // bigint unsigned primary key
            $table->string('apellidos_nombres');
            $table->tinyInteger('estado')->default(1);
            $table->foreignId('id_service')->nullable()->constrained('document_types')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_personals');
    }
};