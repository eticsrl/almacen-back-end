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
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('codigo')->nullable()->after('id');
            // estado tinyint(1) NOT NULL (booleano en Laravel)
            $table->boolean('estado')->default(1)->after('darmin'); // TINYINT(1)
            // estado tinyint(1) NOT NULL (booleano en Laravel)
            $table->integer('usuario_id')->unsigned()->nullable()->after('estado');

             // Ejemplo: eliminar columna
             $table->dropColumn('liname');
             $table->dropColumn('usr');
             $table->dropColumn('estado_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            //
        });
    }
};
