<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('path');                 // caminho no disco "public" ou URL absoluta
            $table->string('disk', 30)->default('public');
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('capa')->default(false);
            $table->string('alt')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_photos');
    }
};
