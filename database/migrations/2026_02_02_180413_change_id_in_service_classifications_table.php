<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up()
    {
        // 1. Desactivar llaves foráneas para evitar errores de restricción
        Schema::disableForeignKeyConstraints();

        // 2. Cambiar el tipo de dato de la columna ID
        // Nota: Usamos DB::statement porque cambiar un Primary Key Autoincrement 
        // a veces da problemas con el helper de Laravel.
        DB::statement('ALTER TABLE service_classifications MODIFY id BIGINT UNSIGNED AUTO_INCREMENT');

        // 3. ¡IMPORTANTE! También debes cambiar todas las tablas que apuntan a esta
        // Por ejemplo, en service_personals:
        DB::statement('ALTER TABLE service_personals MODIFY id_service BIGINT UNSIGNED NULL');

        Schema::enableForeignKeyConstraints();
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE service_classifications MODIFY id TINYINT UNSIGNED AUTO_INCREMENT');
        DB::statement('ALTER TABLE service_personals MODIFY id_service TINYINT UNSIGNED NULL');
        Schema::enableForeignKeyConstraints();
    }
};
