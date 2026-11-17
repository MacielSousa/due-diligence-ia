<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedores')->cascadeOnDelete();
            $table->string('status', 20)->default('pendente')->index();
            $table->string('nivel', 20)->nullable()->index();
            $table->decimal('score', 5, 1)->nullable();
            $table->decimal('confianca', 3, 2)->nullable();
            $table->boolean('requer_revisao')->default(false);
            $table->json('resultado')->nullable();
            $table->json('documentos_usados')->nullable(); // [{id, tipo, sha256}]
            $table->text('erro')->nullable();

            // Decisão humana (comitê de compliance)
            $table->string('decisao', 20)->nullable();
            $table->string('decidido_por')->nullable();
            $table->text('justificativa_decisao')->nullable();
            $table->timestamp('decidido_em')->nullable();

            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
