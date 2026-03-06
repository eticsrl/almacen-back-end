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
        Schema::table('document_types', function (Blueprint $table) {
            // estado tinyint(1) NOT NULL (booleano en Laravel)
            $table->boolean('estado')->default(1); // TINYINT(1)
            // estado tinyint(1) NOT NULL (booleano en Laravel)
            $table->integer('usuario_id')->unsigned()->nullable()->after('estado');


            // Ejemplo: eliminar columna
            $table->dropColumn('cod_servicio');
            $table->dropColumn('usr');
            $table->dropColumn('estado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            //
        });
    }
};
