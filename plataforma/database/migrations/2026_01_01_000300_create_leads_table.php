<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['whatsapp', 'proposta', 'troca', 'financiamento', 'contato', 'bot', 'webmotors'])->index();
            $table->string('nome', 120);
            $table->string('fone', 30)->nullable()->index();
            $table->string('email', 160)->nullable();
            $table->foreignId('veiculo_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->text('mensagem')->nullable();
            $table->enum('status', ['novo', 'em_contato', 'negociando', 'ganho', 'perdido'])->default('novo')->index();
            $table->json('meta')->nullable();          // dados extras: simulação, veículo na troca, origem, utm…
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('origem', 60)->nullable();  // site, bot, webmotors, painel
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
