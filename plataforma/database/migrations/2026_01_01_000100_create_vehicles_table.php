<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('marca', 80)->index();
            $table->string('modelo', 120)->index();
            $table->string('versao', 180)->nullable();
            $table->unsignedSmallInteger('ano_fab');
            $table->unsignedSmallInteger('ano_mod')->index();
            $table->unsignedInteger('km')->default(0)->index();
            $table->decimal('preco', 12, 2)->index();
            $table->decimal('preco_anterior', 12, 2)->nullable();
            $table->string('cambio', 30)->nullable()->index();
            $table->string('combustivel', 30)->nullable()->index();
            $table->string('carroceria', 30)->nullable()->index();
            $table->string('cor', 40)->nullable()->index();
            $table->string('motor', 40)->nullable();
            $table->unsignedTinyInteger('portas')->nullable();
            $table->string('placa_final', 1)->nullable();
            $table->json('opcionais')->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('destaque')->default(false)->index();
            $table->enum('status', ['disponivel', 'reservado', 'vendido'])->default('disponivel')->index();
            $table->string('slug', 220)->unique();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
