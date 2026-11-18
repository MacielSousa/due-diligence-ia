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

**Quer treinar de novo?** O esqueleto vazio (com o seu `Apontamento.php` em andamento) está em
`backend/treino/esqueleto/`. Para praticar, copie esses arquivos por cima de
`app/Domain/DueDiligence/` (mantenha o `Documento.php`) e rode `composer test:desafio`
até os 7 passarem. Enquanto o domínio estiver incompleto, a API responde **501 `domain_incomplete`**.

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
php artisan migrate:fresh --seed   # só na primeira vez / para resetar os dados
php artisan serve
```

```bash
# Terminal 2 — frontend (http://localhost:5180)
cd frontend
npm run dev -- --port 5180
```

O Vite repassa `/api/*` para o Laravel (proxy em `vite.config.ts`), então não há CORS.

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
