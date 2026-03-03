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
        /*
        Schema::create('variables_configuracion', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        */
        Schema::create('variablesConfiguracion', function (Blueprint $table) {
        // id bigint NOT NULL AUTO_INCREMENT (id() crea un bigIncrements por defecto)
            $table->id(); 
            
            // nombre varchar(255) NOT NULL
            $table->string('nombre', 100); 
            
            // observaciones varchar(255) NOT NULL
            $table->string('observaciones', 150); 
            
            // estado tinyint(1) NOT NULL (booleano en Laravel)
            $table->boolean('estado'); 
            
            // tipo int NOT NULL DEFAULT 0
            $table->integer('tipo')->default(0); 
            
            // valor varchar(20) NOT NULL
            $table->string('valor', 20); 

            // Opcional: marca de tiempo (created_at, updated_at)
            $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variables_configuracion');
    }
};
