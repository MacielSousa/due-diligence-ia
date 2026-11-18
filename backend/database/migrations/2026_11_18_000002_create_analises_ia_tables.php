<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // uma execução da análise por IA de um terceiro
        Schema::create('analises_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('terceiro_id')->constrained('terceiros')->cascadeOnDelete();
            $table->string('status', 20)->default('pendente'); // pendente | processando | concluida | falhou
            $table->string('modelo', 100);
            $table->unsignedSmallInteger('ano_referencia');

            // progresso e métricas
            $table->unsignedSmallInteger('documentos_total')->default(0);
            $table->unsignedSmallInteger('documentos_processados')->default(0);
            $table->unsignedSmallInteger('achados_validos')->default(0);
            $table->unsignedSmallInteger('achados_descartados')->default(0);
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->unsignedInteger('duracao_ms')->nullable();

            // resultado
            $table->decimal('score', 5, 1)->nullable();
            $table->string('nivel', 20)->nullable();
            $table->boolean('eliminatorio')->default(false);
            $table->string('recomendacao', 40)->nullable();
            $table->json('explicacao')->nullable(); // o "porquê" do score, peso a peso
            $table->text('erro')->nullable();
            $table->timestamps();
        });

        // o que a IA leu e respondeu em cada documento
        Schema::create('analise_ia_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analise_ia_id')->constrained('analises_ia')->cascadeOnDelete();
            $table->foreignId('anexo_id')->nullable()->constrained('anexos')->nullOnDelete();
            $table->string('nome');
            $table->string('categoria', 40);
            $table->string('status', 20)->default('pendente'); // pendente | processando | concluido | ignorado
            $table->unsignedInteger('caracteres')->nullable();
            $table->unsignedInteger('tokens_entrada')->nullable();
            $table->unsignedInteger('tokens_saida')->nullable();
            $table->unsignedInteger('duracao_ms')->nullable();
            $table->json('achados')->nullable(); // achados com valido/motivo
            $table->longText('resposta_bruta')->nullable(); // auditoria: o que o modelo devolveu
            $table->text('erro')->nullable();
            $table->timestamps();
        });

        // apontamentos passam a guardar de onde vieram
        Schema::table('apontamentos', function (Blueprint $table) {
            $table->string('origem', 10)->default('manual'); // manual | ia
            $table->text('trecho')->nullable();              // evidência literal (quando veio da IA)
            $table->foreignId('anexo_id')->nullable()->constrained('anexos')->nullOnDelete();
            $table->foreignId('analise_ia_id')->nullable()->constrained('analises_ia')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('apontamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('analise_ia_id');
            $table->dropConstrainedForeignId('anexo_id');
            $table->dropColumn(['origem', 'trecho']);
        });
        Schema::dropIfExists('analise_ia_documentos');
        Schema::dropIfExists('analises_ia');
    }
};
