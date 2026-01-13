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
        Schema::create('nutricion_etapa_fenologica_tipo_cultivo', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('etapa_fenologica_tipo_cultivo_id');
            //$table->foreign('etapa_fenologica_tipo_cultivo_id', 'nutri_etapa_tipo_cultivo_fk')
                //->references('id')
                //->on('etapa_fenologica_tipo_cultivo')
                //->onDelete('cascade');

            $table->string('variable')->nullable();
            $table->integer('min')->nullable();
            $table->integer('optimo_min')->nullable();
            $table->integer('optimo_max')->nullable();
            $table->integer('max')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutricion_etapa_fenologica_tipo_cultivo');
    }
};
