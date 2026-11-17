<?php

namespace Modules\Documentos\Tests\Integration;

use Illuminate\Http\UploadedFile;
use Modules\Documentos\Contracts\ConteudoDocumentoStore;
use Modules\Documentos\Models\Documento;
use Modules\Fornecedores\Models\Fornecedor;
use PHPUnit\Framework\Attributes\Test;
use Tests\IntegrationTestCase;

class DocumentoApiTest extends IntegrationTestCase
{
    #[Test]
    public function grava_metadados_no_mysql_e_texto_no_mongodb(): void
    {
        $fornecedor = Fornecedor::factory()->create();
        $texto = "Certidão negativa de débitos trabalhistas.\nNada consta.";

        $resposta = $this->postJson("/api/fornecedores/{$fornecedor->id}/documentos", [
            'tipo' => 'cndt',
            'titulo' => 'CNDT',
            'emitido_em' => now()->subDays(5)->toDateString(),
            'conteudo' => $texto,
        ])->assertCreated()
            ->assertJsonPath('data.tipo', 'cndt')
            ->assertJsonPath('data.sha256', hash('sha256', $texto));

        $id = $resposta->json('data.id');
        $this->assertDatabaseHas('documentos', ['id' => $id, 'fornecedor_id' => $fornecedor->id]);
        $this->assertSame($texto, app(ConteudoDocumentoStore::class)->buscar($id));
    }

    #[Test]
    public function aceita_upload_de_arquivo_texto(): void
    {
        $fornecedor = Fornecedor::factory()->create();
        $arquivo = UploadedFile::fake()->createWithContent('balanco.txt', 'Lucro líquido de R$ 10 milhões.');

        $this->post("/api/fornecedores/{$fornecedor->id}/documentos", [
            'tipo' => 'balanco',
            'titulo' => 'Balanço 2025',
            'arquivo' => $arquivo,
        ], ['Accept' => 'application/json'])->assertCreated();

        $documento = Documento::firstOrFail();
        $this->assertSame('Lucro líquido de R$ 10 milhões.', app(ConteudoDocumentoStore::class)->buscar($documento->id));
    }

    #[Test]
    public function valida_tipo_e_conteudo(): void
    {
        $fornecedor = Fornecedor::factory()->create();

        $this->postJson("/api/fornecedores/{$fornecedor->id}/documentos", ['tipo' => 'inexistente', 'titulo' => 'X'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo', 'conteudo']);
    }

    #[Test]
    public function lista_os_tipos_aceitos(): void
    {
        $this->getJson('/api/documentos/tipos')
            ->assertOk()
            ->assertJsonFragment(['valor' => 'lista_suja', 'obrigatorio' => true]);
    }
}
