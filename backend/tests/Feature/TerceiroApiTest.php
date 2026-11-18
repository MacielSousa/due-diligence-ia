<?php

namespace Tests\Feature;

use App\Models\Terceiro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerceiroApiTest extends TestCase
{
    use RefreshDatabase;

    private function pf(array $dados = []): Terceiro
    {
        return Terceiro::create($dados + ['tipo' => 'pf', 'nome' => 'Ana Souza', 'documento' => '52998224725']);
    }

    private function pj(array $dados = []): Terceiro
    {
        return Terceiro::create($dados + ['tipo' => 'pj', 'nome' => 'Empresa X', 'documento' => '11222333000181']);
    }

    public function test_lista_terceiros_ordenados_por_nome_com_contagens(): void
    {
        $this->pf(['nome' => 'Zeca', 'documento' => '11144477735']);
        $this->pf();

        $this->getJson('/api/terceiros')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nome', 'Ana Souza')
            ->assertJsonPath('data.1.nome', 'Zeca')
            ->assertJsonPath('data.0.apontamentos_count', 0);
    }

    public function test_filtra_por_tipo_e_busca_por_documento_com_mascara(): void
    {
        $this->pf();
        $this->pj();

        $this->getJson('/api/terceiros?tipo=pj')->assertJsonCount(1, 'data')->assertJsonPath('data.0.tipo', 'pj');
        $this->getJson('/api/terceiros?busca=529.982')->assertJsonCount(1, 'data')->assertJsonPath('data.0.nome', 'Ana Souza');
        $this->getJson('/api/terceiros?busca=empresa')->assertJsonCount(1, 'data');
    }

    public function test_cria_pessoa_fisica_removendo_mascara_do_documento(): void
    {
        $this->postJson('/api/terceiros', ['tipo' => 'pf', 'nome' => 'Maria', 'documento' => '111.444.777-35'])
            ->assertCreated()
            ->assertJsonPath('data.documento', '11144477735')
            ->assertJsonPath('data.pep', false);

        $this->assertDatabaseHas('terceiros', ['documento' => '11144477735']);
    }

    public function test_pessoa_juridica_nunca_e_pep(): void
    {
        $this->postJson('/api/terceiros', ['tipo' => 'pj', 'nome' => 'Empresa', 'documento' => '11.222.333/0001-81', 'pep' => true])
            ->assertCreated()
            ->assertJsonPath('data.pep', false);
    }

    public function test_valida_campos_obrigatorios(): void
    {
        $this->postJson('/api/terceiros', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo', 'nome', 'documento']);
    }

    public function test_documento_precisa_ter_o_tamanho_do_tipo(): void
    {
        // CNPJ (14 dígitos) numa pessoa física
        $this->postJson('/api/terceiros', ['tipo' => 'pf', 'nome' => 'Maria', 'documento' => '11222333000181'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documento']);
    }

    public function test_cpf_com_digito_verificador_invalido_e_recusado(): void
    {
        $this->postJson('/api/terceiros', ['tipo' => 'pf', 'nome' => 'Maria', 'documento' => '123.456.789-00'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.documento.0', 'O CPF informado é inválido.');

        $this->postJson('/api/terceiros', ['tipo' => 'pf', 'nome' => 'Maria', 'documento' => '111.111.111-11'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documento']);
    }

    public function test_cnpj_com_digito_verificador_invalido_e_recusado(): void
    {
        $this->postJson('/api/terceiros', ['tipo' => 'pj', 'nome' => 'Empresa', 'documento' => '11.222.333/0001-80'], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.documento.0', 'The CNPJ number is invalid.');
    }

    public function test_documento_e_unico(): void
    {
        $this->pf();

        $this->postJson('/api/terceiros', ['tipo' => 'pf', 'nome' => 'Outra', 'documento' => '529.982.247-25'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documento']);
    }

    public function test_atualiza_mas_nao_permite_trocar_o_tipo(): void
    {
        $ana = $this->pf();

        $this->putJson("/api/terceiros/{$ana->id}", ['nome' => 'Ana Lima', 'documento' => '52998224725', 'pep' => true])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Ana Lima')
            ->assertJsonPath('data.pep', true);

        $this->putJson("/api/terceiros/{$ana->id}", ['tipo' => 'pj', 'nome' => 'Ana Lima', 'documento' => '52998224725'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo']);
    }

    public function test_detalhe_traz_apontamentos_e_socios(): void
    {
        $ana = $this->pf();
        $empresa = $this->pj();
        $empresa->apontamentos()->create(['tipo' => 'sancao', 'ano' => 2020]);
        $empresa->socios()->create(['socio_id' => $ana->id, 'participacao' => 30]);

        $this->getJson("/api/terceiros/{$empresa->id}")
            ->assertOk()
            ->assertJsonPath('data.apontamentos.0.tipo', 'sancao')
            ->assertJsonPath('data.socios.0.participacao', 30)
            ->assertJsonPath('data.socios.0.socio.nome', 'Ana Souza');
    }

    public function test_exclui_em_cascata(): void
    {
        $ana = $this->pf();
        $empresa = $this->pj();
        $ana->apontamentos()->create(['tipo' => 'sancao', 'ano' => 2020]);
        $empresa->socios()->create(['socio_id' => $ana->id, 'participacao' => 30]);

        $this->deleteJson("/api/terceiros/{$ana->id}")
            ->assertOk()
            ->assertExactJson(['success' => true, 'message' => __('api.terceiro.excluido'), 'data' => null]);

        $this->assertDatabaseMissing('terceiros', ['id' => $ana->id]);
        $this->assertDatabaseCount('apontamentos', 0);
        $this->assertDatabaseCount('socios', 0);
    }

    public function test_terceiro_inexistente_retorna_404_no_formato_padrao(): void
    {
        $this->getJson('/api/terceiros/999')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'message' => 'Recurso não encontrado.', 'code' => 'not_found']);
    }

    public function test_sucesso_segue_o_formato_padrao_com_mensagem(): void
    {
        $this->postJson('/api/terceiros', ['tipo' => 'pf', 'nome' => 'Maria', 'documento' => '111.444.777-35'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Terceiro cadastrado com sucesso.')
            ->assertJsonStructure(['success', 'message', 'data' => ['id', 'tipo', 'nome', 'documento', 'pep']]);
    }

    public function test_erro_de_validacao_segue_o_formato_padrao(): void
    {
        $this->postJson('/api/terceiros', ['tipo' => 'pf', 'nome' => 'Jo', 'documento' => '123'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonPath('message', 'Os dados informados são inválidos.')
            ->assertJsonPath('errors.nome.0', 'O campo nome deve ter pelo menos 3 caracteres.')
            ->assertJsonPath('errors.documento.0', 'O campo documento deve ter 11 dígitos.');
    }

    public function test_mensagens_respeitam_o_accept_language(): void
    {
        $this->postJson('/api/terceiros', [], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The given data is invalid.')
            ->assertJsonPath('errors.nome.0', 'The name field is required.');

        $this->getJson('/api/terceiros/999', ['Accept-Language' => 'en-US,en;q=0.9'])
            ->assertJsonPath('message', 'Resource not found.');
    }

    public function test_metodo_nao_permitido_retorna_405_no_formato_padrao(): void
    {
        $this->patchJson('/api/relatorio-risco')
            ->assertStatus(405)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'method_not_allowed');
    }
}
