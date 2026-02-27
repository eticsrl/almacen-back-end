<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up()
    {
     // 1. Desactivar checks de integridad
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // 2. Buscar TODAS las llaves foráneas que apuntan a document_types.id
    // Esto evita que tengamos que adivinar los nombres de las tablas
    $foreignKeys = DB::select("
        SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_NAME = 'document_types'
        AND REFERENCED_COLUMN_NAME = 'id'
        AND TABLE_SCHEMA = DATABASE()
    ");

    // 3. Eliminar esas llaves foráneas temporalmente
    foreach ($foreignKeys as $fk) {
        DB::statement("ALTER TABLE {$fk->TABLE_NAME} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
    }

    // 4. AHORA SÍ: Cambiar el ID del padre a BIGINT
    DB::statement('ALTER TABLE document_types MODIFY id BIGINT UNSIGNED AUTO_INCREMENT');

    // 5. Cambiar el tipo de dato en cada tabla hija y volver a crear la relación
    foreach ($foreignKeys as $fk) {
        // Cambiamos la columna de la tabla hija a BIGINT UNSIGNED
        DB::statement("ALTER TABLE {$fk->TABLE_NAME} MODIFY {$fk->COLUMN_NAME} BIGINT UNSIGNED");

        // Recreamos la llave foránea
        DB::statement("
            ALTER TABLE {$fk->TABLE_NAME} 
            ADD CONSTRAINT {$fk->CONSTRAINT_NAME} 
            FOREIGN KEY ({$fk->COLUMN_NAME}) 
            REFERENCES document_types(id)
        ");
    }

    // 6. Reactivar la protección
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        DB::statement('ALTER TABLE service_classifications MODIFY id TINYINT UNSIGNED AUTO_INCREMENT');
        DB::statement('ALTER TABLE service_personals MODIFY id_service TINYINT UNSIGNED NULL');
        Schema::enableForeignKeyConstraints();
    }
};
