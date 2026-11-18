# Desafio Due Diligence

Treino de live coding (tema: avaliação de risco de terceiros), separado em:

| Pasta | Stack |
|---|---|
| `backend/` | Laravel 12 — API REST (SQLite), respostas padronizadas e mensagens em pt-BR / en |
| `frontend/` | Vue 3 + **TypeScript**, Vite, Vue Router, Pinia, PrimeVue 4, Tailwind CSS 3, vue-i18n |

## O desafio

O domínio está **implementado** em `backend/app/Domain/DueDiligence/`
(`Documento`, `Apontamento`, `Terceiro`, `PessoaFisica`, `PessoaJuridica`, `AnalisadorDeRisco`).

```bash
cd backend
composer desafio        # roda o script do desafio (desafio.php)
composer test:desafio   # 7 testes de aceitação do cálculo de risco
```

**Quer treinar de novo?** O esqueleto vazio está em `backend/treino/` (veja o README de lá).
Enquanto o domínio estiver incompleto, a API responde **501 `domain_incomplete`**.

> O backend é **só API**: não tem views nem Vite próprios. A interface é a pasta `frontend/`.

## Validação de documentos

CPF e CNPJ são validados pelos **dígitos verificadores** em três lugares, com o mesmo algoritmo:
- `app/Domain/DueDiligence/Documento.php` — usado pelo domínio;
- `app/Rules/CpfValido.php` e `CnpjValido.php` — Form Request da API (mensagem em pt-BR/en);
- `frontend/src/utils/documento.ts` — avisa no formulário antes de enviar.

## Documentos anexados

Cada terceiro pode ter arquivos anexados (PDF, JPG, PNG até 2 MB), por categoria: contrato social,
certidão negativa, documento de identidade, comprovante de endereço ou outro.
Os arquivos ficam em `backend/storage/app/private/terceiros/{id}/anexos/` (fora da pasta pública)
e são baixados pela API. Excluir o anexo ou o terceiro apaga também o arquivo.

## Rodar o projeto (2 terminais no WSL)

```bash
# Terminal 1 — backend (http://localhost:8000)
cd backend
composer setup   # só na 1ª vez: instala, cria .env e o banco SQLite com os dados de exemplo
composer dev     # = php artisan serve
```

```bash
# Terminal 2 — frontend (http://localhost:5180)
cd frontend
npm install      # só na 1ª vez
npm run dev -- --port 5180
```

O Vite repassa `/api/*` para o Laravel (proxy em `vite.config.ts`), então não há CORS.

## Análise de documentos por IA (Llama local)

A Llama 3.2 3B (via **Ollama**) lê os documentos anexados e aponta os riscos; o sistema
confere cada achado e calcula o score. Tudo roda em **fila**, em dois jobs:

```
POST /analises-ia ──► AnalisarDocumentosJob (Job 1)              ──► RegistrarResultadoAnaliseJob (Job 2)
  202 na hora          para cada PDF: extrai texto → limpa →          troca os apontamentos de origem "ia",
                       Llama → travas do código → grava achados       calcula o score com o domínio (explicado
                       e o tempo de cada documento                    peso a peso), nível, recomendação e métricas
```

- **Travas do código** (o modelo sugere, o sistema decide): tipo válido, trecho citado precisa
  existir **literalmente** no documento, não pode vir de certidão negativa, ano precisa aparecer
  no texto. Achado descartado fica registrado com o motivo.
- **Eliminatório:** sanção vigente que impede licitar/contratar ⇒ recomendação **reprovar**,
  independente do score.
- **Métricas guardadas:** duração total, espera na fila, tempo e tokens por documento, achados
  válidos/descartados, modelo usado e a resposta bruta do modelo (auditoria).
- **"Por que esse score?":** peso de cada apontamento, depreciação por idade, adicional de PEP,
  score de cada sócio × participação, soma e teto.

**Rodar** (além do backend e do frontend):
```bash
cd backend && composer worker            # executa os jobs da fila
./ia/criar-modelo.sh                     # cria o modelo "due-diligence" no Ollama (uma vez)
```

**Modelo** `due-diligence` (`ia/Modelfile`): `llama3.2:3b` + regras + exemplos resolvidos.
Avaliação nos documentos fictícios de `exemplos/fm-solucoes/`:

| Modelo | Acertos | Precisão | Cobertura |
|---|---|---|---|
| `llama3.2:3b` sem preparo | 1 de 4 | 100% | 25% |
| `due-diligence` (Modelfile) | **4 de 4** | **100%** | **100%** |

Fine-tuning (LoRA) em `ia/treino/`: `gerar_dataset.py` (600 exemplos sintéticos),
`instalar.sh`, `treinar.py` (QLoRA para GPU de 4 GB) e `criar-modelo-ft.sh`.

## Padrão de resposta da API

Toda resposta segue o mesmo formato (`app/Http/Responses/ApiResponse.php`):

```jsonc
// sucesso
{ "success": true, "message": "Terceiro cadastrado com sucesso.", "data": { ... }, "meta": { ... } }

// erro
{ "success": false, "message": "Os dados informados são inválidos.", "code": "validation_error",
  "errors": { "nome": ["O campo nome é obrigatório."] }, "detail": "..." }
```

| `code` | HTTP | Quando |
|---|---|---|
| `validation_error` | 422 | Form Request falhou (vem com `errors` por campo) |
| `business_rule` | 422 | Regra do domínio violada (CPF inválido, ciclo societário…) |
| `not_found` | 404 | Recurso inexistente |
| `method_not_allowed` | 405 | Método HTTP errado |
| `domain_incomplete` | 501 | Classes do desafio incompletas (`detail` diz o que falta) |
| `internal_error` | 500 | Erro inesperado (`detail` só com `APP_DEBUG=true`) |

**Idioma:** o frontend envia `Accept-Language` (idioma escolhido na tela) e o middleware
`DefinirIdioma` faz o Laravel responder no mesmo idioma — inclusive as mensagens de validação
(`lang/pt_BR`, `lang/en`).

## Endpoints

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/terceiros?busca=&tipo=pf\|pj` | Lista |
| POST | `/api/terceiros` | Cria (`tipo`, `nome`, `documento`, `pep`) |
| GET | `/api/terceiros/{id}` | Detalhe com apontamentos e sócios |
| PUT | `/api/terceiros/{id}` | Atualiza (`nome`, `documento`, `pep`) |
| DELETE | `/api/terceiros/{id}` | Exclui (cascata) |
| POST | `/api/terceiros/{id}/apontamentos` | `tipo`, `ano` |
| DELETE | `/api/terceiros/{id}/apontamentos/{apontamentoId}` | |
| POST | `/api/terceiros/{id}/socios` | `socio_id`, `participacao` (só PJ, soma ≤ 100) |
| DELETE | `/api/terceiros/{id}/socios/{socioId}` | |
| GET | `/api/terceiros/{id}/anexos` | Lista os documentos anexados |
| POST | `/api/terceiros/{id}/anexos` | Upload multipart: `categoria`, `arquivo` |
| GET | `/api/terceiros/{id}/anexos/{anexoId}/download` | Baixa o arquivo |
| DELETE | `/api/terceiros/{id}/anexos/{anexoId}` | Remove o anexo e o arquivo |
| GET | `/api/terceiros/{id}/risco?ano=2026` | Score + nível (usa o domínio) |
| GET | `/api/relatorio-risco?ano=2026` | Ranking de todos (`meta.ano`) |

## Frontend: uma responsabilidade por camada

```
componente/página ──> store (Pinia) ──> service ──> useApi ──> fetch
       │                 estado          endpoints     HTTP, idioma, formato padrão, ApiError
       └── useNotificacao().executar(): toast com a `message` da API + erros nos campos
```

```
frontend/src/
  types/          api.ts (ApiSucesso, ApiFalha, ErrorCode), terceiro.ts, risco.ts, router.d.ts
  composables/    useApi.ts (conector HTTP + ApiError), useNotificacao.ts (padrão de mensagens)
  services/       terceirosService.ts, riscoService.ts  (quais endpoints + tipo do retorno)
  stores/         app.ts (idioma), terceiros.ts, risco.ts  (estado)
  layouts/        DefaultLayout.vue  (header + <RouterView/> + footer)
  pages/          RelatorioPage, TerceirosPage, TerceiroDetalhePage
  components/
    layout/       AppHeader, AppNav (menu gerado pelas rotas), LanguageSwitcher, AppFooter
    ui/           PageHeader, SectionCard, ApiErrorMessage, CampoErro
    terceiros/    TerceiroDialog, ApontamentosCard, SociosCard
    risco/        RiscoCard, ResumoNiveis, NivelTag
  i18n/           pt-BR.json, en.json
  router/         rotas filhas do DefaultLayout (meta: titulo, menu, icone, menuPai)
```

Regras do padrão de mensagens:
- **Ação** (salvar, excluir, adicionar) → `notificacao.executar(() => store.acao(), { erros })`:
  toast de sucesso com a `message` do backend; em erro, toast + `errors` nos campos (`<CampoErro>`).
- **Carregamento** (lista, detalhe, risco) → o erro fica no estado da store e aparece no lugar do
  conteúdo com `<ApiErrorMessage>` (decide a cor pelo `code`).

## Testes

```bash
cd backend
composer test:api       # 53 testes (API, anexos, validação de CPF/CNPJ, formato padrão, idioma)
composer test:desafio   # 7 testes de aceitação do cálculo de risco
php artisan test        # tudo

cd frontend
npm run type-check      # TypeScript (vue-tsc)
npm run build           # type-check + build
```
