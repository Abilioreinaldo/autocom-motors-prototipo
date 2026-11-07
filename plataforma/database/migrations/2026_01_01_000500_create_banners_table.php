<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->string('subtitulo', 200)->nullable();
            $table->string('imagem')->nullable();
            $table->string('link')->nullable();
            $table->string('cta', 60)->nullable();
            $table->string('posicao', 30)->default('home_hero'); // home_hero, home_faixa, estoque_topo
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamp('inicio_em')->nullable();
            $table->timestamp('fim_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
