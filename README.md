# Avaliação de Risco de Fornecedores com LLM — Prova de Conceito

Monorepo que implementa a resposta da [Questão 6](RESPOSTA.md): usar um LLM para **resumir documentos**
de uma construtora e classificar o **risco de contratação** em **BAIXO, MÉDIO ou ELEVADO**, com regras
determinísticas, evidência verificada e revisão humana.

```
.
├── RESPOSTA.md                 # resposta escrita da questão
├── packages/risk-engine/       # motor de risco em PHP puro (sem framework)
├── apps/api/                   # Laravel 13 + Sail (MySQL, Redis, MongoDB), modular
│   └── modules/
│       ├── Fornecedores/       # cadastro e validação de CNPJ
│       ├── Documentos/         # metadados no MySQL, texto bruto no MongoDB
│       ├── IA/                 # adaptador do Claude + driver heurístico offline
│       ├── Auditoria/          # log de toda chamada ao LLM no MongoDB
│       └── Avaliacao/          # orquestra o pipeline em job na fila Redis
└── apps/web/                   # Vue 3 + Vite + TypeScript (SPA)
```

## Arquitetura

```
Vue (5173) ──/api──► Laravel (8080) ──► MySQL   fornecedores, documentos, avaliações
                         │                MongoDB texto dos documentos + auditoria do LLM
                         └─ job ─► Redis ─► worker "queue"
                                              └─► risk-engine
                                                    1. map: resumo + achados por documento (LLM, JSON Schema)
                                                    2. evidência literal verificada em código
                                                    3. eliminatórios (Lista Suja, CEIS/CNEP, falência…)
                                                    4. reduce: nota 0–100 por dimensão (LLM)
                                                    5. score ponderado + classificação (código)
                                                    6. confiança e revisão humana obrigatória
```

| Container (Sail) | Papel |
|---|---|
| `laravel.test` | API REST (PHP 8.4) |
| `queue` | worker `queue:work redis` que processa as avaliações |
| `web` | Vite servindo o Vue, com proxy de `/api` para o Laravel |
| `mysql` | dados relacionais |
| `redis` | fila e cache |
| `mongodb` | texto bruto dos documentos e auditoria (prompt, resposta, modelo, tokens) |

## Como rodar

Pré-requisito: Docker (no WSL ou Docker Desktop com integração WSL).

```bash
cd apps/api
cp .env.example .env            # primeira vez
composer install                # ou: docker run --rm -v "$PWD/../..:/app" -w /app/apps/api composer install
./vendor/bin/sail up -d         # sobe api, queue, web, mysql, redis, mongodb
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

- Frontend: <http://localhost:5173>
- API: <http://localhost:8080/api/fornecedores>

O seed cria 3 construtoras **fictícias**: uma de risco baixo, uma médio e uma elevado (consta na Lista Suja).

Avaliar pelo terminal (síncrono):

```bash
./vendor/bin/sail artisan risco:avaliar 3
```

### Usando o Claude de verdade

Por padrão `IA_DRIVER=heuristico`: um "LLM" de palavras-chave que roda offline, sem custo, para demo e
testes. Para usar o Claude, edite `apps/api/.env`:

```dotenv
IA_DRIVER=anthropic
ANTHROPIC_API_KEY=sk-ant-...
IA_MODELO=claude-opus-5-5
IA_ESFORCO=high
```

e reinicie o worker: `./vendor/bin/sail restart queue`.

## Testes

| Suíte | Onde | Comando |
|---|---|---|
| Unitários do motor | `packages/risk-engine/tests` | `cd packages/risk-engine && composer install && vendor/bin/phpunit` |
| Unitários dos módulos | `apps/api/modules/*/Tests/Unit` | `./vendor/bin/sail artisan test --testsuite=Unit` |
| Integração (HTTP + MySQL + Redis + MongoDB) | `apps/api/modules/*/Tests/Integration`, `apps/api/tests/Integration` | `./vendor/bin/sail artisan test --testsuite=Integration` |
| Frontend (Vitest) | `apps/web/src/__tests__` | `./vendor/bin/sail exec web npm test` |

## API

| Método | Rota | Descrição |
|---|---|---|
| GET/POST | `/api/fornecedores` | lista / cadastra |
| GET | `/api/fornecedores/{id}` | detalhe |
| GET/POST | `/api/fornecedores/{id}/documentos` | lista / anexa (texto ou arquivo `.txt`) |
| GET | `/api/documentos/tipos` | tipos aceitos, obrigatórios e validade |
| GET/POST | `/api/fornecedores/{id}/avaliacoes` | histórico / solicita (202, vai para a fila) |
| GET | `/api/avaliacoes/{id}` | parecer completo |
| POST | `/api/avaliacoes/{id}/decisao` | decisão do comitê |
| GET | `/api/avaliacoes/{id}/auditoria` | chamadas ao LLM (MongoDB) |

## Limites da PoC

- Sem autenticação/autorização (em produção: SSO e perfis analista × comitê).
- Documentos entram como texto; extração de PDF/OCR e integração com fontes oficiais
  (Receita, TST, MTE, CGU) ficam para a próxima etapa.
- O driver heurístico só existe para demo e testes: não substitui o modelo.
- Falta o conjunto de avaliação (evals) com empresas já classificadas por analistas para medir
  a qualidade de prompts e modelos antes de usar em decisões reais.
