<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terceiro_id')->constrained('terceiros')->cascadeOnDelete();
            $table->string('categoria', 40);
            $table->string('nome_original');
            $table->string('caminho'); // caminho no disco "local" (storage/app/private)
            $table->string('mime', 100);
            $table->unsignedBigInteger('tamanho'); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
    }
};
