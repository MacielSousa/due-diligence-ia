<?php

namespace Tests\Feature;

use App\Models\Anexo;
use App\Models\Terceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnexoApiTest extends TestCase
{
    use RefreshDatabase;

    private Terceiro $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Anexo::DISCO); // disco em memória: não grava nada de verdade
        $this->empresa = Terceiro::create(['tipo' => 'pj', 'nome' => 'Empresa X', 'documento' => '11222333000181']);
    }

    private function enviar(array $dados)
    {
        return $this->post("/api/terceiros/{$this->empresa->id}/anexos", $dados, ['Accept' => 'application/json']);
    }

    public function test_anexa_pdf_e_guarda_o_arquivo(): void
    {
        $resposta = $this->enviar([
            'categoria' => 'contrato_social',
            'arquivo' => UploadedFile::fake()->create('contrato.pdf', 120, 'application/pdf'),
        ]);

        $resposta->assertCreated()
            ->assertJsonPath('message', 'Documento anexado com sucesso.')
            ->assertJsonPath('data.nome', 'contrato.pdf')
            ->assertJsonPath('data.categoria', 'contrato_social');

        $anexo = Anexo::firstOrFail();
        Storage::disk(Anexo::DISCO)->assertExists($anexo->caminho);
        $this->assertStringStartsWith("terceiros/{$this->empresa->id}/anexos/", $anexo->caminho);
    }

    public function test_detalhe_do_terceiro_traz_os_anexos(): void
    {
        // create() em vez de image(): o PHP deste ambiente não tem a extensão GD
        $this->enviar(['categoria' => 'outro', 'arquivo' => UploadedFile::fake()->create('foto.png', 15, 'image/png')]);

        $this->getJson("/api/terceiros/{$this->empresa->id}")
            ->assertJsonPath('data.anexos.0.nome', 'foto.png')
            ->assertJsonPath('data.anexos.0.download_url', "/api/terceiros/{$this->empresa->id}/anexos/1/download");
    }

    public function test_valida_categoria_tipo_e_tamanho_do_arquivo(): void
    {
        $this->enviar(['categoria' => 'xpto', 'arquivo' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['categoria', 'arquivo']);

        $this->enviar(['categoria' => 'outro', 'arquivo' => UploadedFile::fake()->create('grande.pdf', 3000, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonPath('errors.arquivo.0', 'O arquivo não pode ter mais de 2048 kilobytes.');
    }

    public function test_download_devolve_o_arquivo_com_o_nome_original(): void
    {
        $this->enviar(['categoria' => 'outro', 'arquivo' => UploadedFile::fake()->create('certidao.pdf', 10, 'application/pdf')]);
        $anexo = Anexo::firstOrFail();

        $this->get("/api/terceiros/{$this->empresa->id}/anexos/{$anexo->id}/download")
            ->assertOk()
            ->assertDownload('certidao.pdf');
    }

    public function test_excluir_anexo_apaga_o_arquivo(): void
    {
        $this->enviar(['categoria' => 'outro', 'arquivo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]);
        $anexo = Anexo::firstOrFail();

        $this->deleteJson("/api/terceiros/{$this->empresa->id}/anexos/{$anexo->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Documento removido.');

        Storage::disk(Anexo::DISCO)->assertMissing($anexo->caminho);
        $this->assertDatabaseCount('anexos', 0);
    }

    public function test_excluir_terceiro_apaga_os_arquivos_dos_anexos(): void
    {
        $this->enviar(['categoria' => 'outro', 'arquivo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]);
        $anexo = Anexo::firstOrFail();

        $this->deleteJson("/api/terceiros/{$this->empresa->id}")->assertOk();

        Storage::disk(Anexo::DISCO)->assertMissing($anexo->caminho);
    }

    public function test_nao_baixa_anexo_de_outro_terceiro(): void
    {
        $this->enviar(['categoria' => 'outro', 'arquivo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]);
        $outro = Terceiro::create(['tipo' => 'pf', 'nome' => 'Ana', 'documento' => '52998224725']);

        $this->getJson("/api/terceiros/{$outro->id}/anexos/1/download")
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');
    }
}
