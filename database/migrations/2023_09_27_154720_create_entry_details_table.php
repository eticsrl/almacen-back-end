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
        Schema::create('entry_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ingreso_id')->index();
            $table->unsignedInteger('medicamento_id')->index();
            $table->string('lote')->index();
            $table->date('fecha_vencimiento')->index();
            $table->integer('cantidad');
            $table->decimal('costo_unitario',15,4);
            $table->decimal('costo_total',15,4);
            $table->integer('stock_actual');//->default(0);
            $table->string('observaciones');
            $table->unsignedInteger('estado_id');
            $table->integer('usr');
            $table->integer('item_id')->nullable()->index();
            $table->unsignedInteger('receta_item_id')->nullable()->index();
            $table->unsignedInteger('origen_discharge_detail_id')->nullable()->index();


            $table->timestamps();

            $table->foreign ('ingreso_id')->references('id')->on('entries');
            $table->foreign ('medicamento_id')->references('id')->on('medicines');




        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         // Por si el motor no deshabilita checks en rollback
    Schema::disableForeignKeyConstraints();

    Schema::table('entry_details', function (Blueprint $table) {
        // Suelta SOLO las FKs que realmente existen
        try { $table->dropForeign(['ingreso_id']); } catch (\Throwable $e) {}
        try { $table->dropForeign(['medicamento_id']); } catch (\Throwable $e) {}
        // Si en algún momento creaste estas FKs, suéltalas también:
        try { $table->dropForeign(['item_id']); } catch (\Throwable $e) {}
        try { $table->dropForeign(['receta_item_id']); } catch (\Throwable $e) {}
        try { $table->dropForeign(['origen_discharge_detail_id']); } catch (\Throwable $e) {}
    });

    Schema::dropIfExists('entry_details');

    Schema::enableForeignKeyConstraints();
    }
};
