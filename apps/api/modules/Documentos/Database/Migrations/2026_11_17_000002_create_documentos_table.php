<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->cascadeOnDelete();
            $table->string('tipo', 50);
            $table->string('titulo');
            $table->date('emitido_em')->nullable();
            $table->char('sha256', 64);
            $table->unsignedInteger('tamanho_bytes');
            $table->timestamps();

            $table->index(['fornecedor_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
