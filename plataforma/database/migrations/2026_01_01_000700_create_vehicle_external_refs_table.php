<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_external_refs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 40)->default('webmotors');
            $table->string('external_id', 80);
            $table->string('source_url')->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->json('payload')->nullable();
            $table->json('locked_fields')->nullable(); // campos editados no painel que o sync não sobrescreve
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->unique(['vehicle_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_external_refs');
    }
};
