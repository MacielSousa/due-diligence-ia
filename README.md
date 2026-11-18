# Avaliação de Risco de Fornecedores com LLM

Prova de conceito que usa um LLM para **resumir os documentos de uma construtora** e classificar o
**risco de contratação** em **BAIXO, MÉDIO ou ELEVADO**, com evidência verificada, regras
determinísticas e revisão humana.

> A resposta escrita da questão está em [RESPOSTA.md](RESPOSTA.md).

| Documento | Conteúdo |
|---|---|
| [docs/arquitetura.md](docs/arquitetura.md) | Como funciona: containers, módulos, pipeline, dados, fluxo, decisões |
| [docs/api.md](docs/api.md) | Referência da API REST com exemplos |
| [docs/operacao.md](docs/operacao.md) | Trocar o modelo de IA, GPU, testes, problemas comuns |

## Em uma frase

O LLM lê cada documento e extrai achados **com trecho literal**. O código confere se o trecho existe,
aplica regras eliminatórias (Lista Suja, CEIS/CNEP, falência…) e calcula o nível de risco. O comitê
decide.

```
Vue (5173) ─► Laravel API (8080) ─► MySQL · MongoDB
                   └─ fila Redis ─► worker ─► risk-engine ─► LLM (heurístico | Llama local | Claude)
```

## Stack

| Camada | Tecnologia |
|---|---|
| Motor de risco | PHP 8.2+ puro (`packages/risk-engine`) |
| API | Laravel 13, módulos próprios, Laravel Sail |
| Frontend | Vue 3, Vite, TypeScript |
| Bancos | MySQL 8.4 (relacional), MongoDB (texto e auditoria), Redis (fila) |
| IA | Heurístico offline · Llama 3.2 via Ollama · Claude via Anthropic PHP SDK |
| Testes | PHPUnit (unitários e integração), Vitest |

---

## Como executar

### 1. Pré-requisitos

| Item | Observação |
|---|---|
| **Docker** com Compose v2 | No Windows: dentro do **WSL 2** (Docker Engine no Ubuntu ou Docker Desktop com integração WSL) |
| **Git** | |
| ~6 GB livres em disco | Imagens dos containers (+2 GB se usar o Llama) |

Não é preciso ter PHP, Composer nem Node na máquina: tudo roda em containers.

> **Windows:** execute todos os comandos **dentro do WSL** (terminal do Ubuntu) e mantenha o projeto no
> sistema de arquivos do Linux (ex.: `~/projetos`), não em `/mnt/c`. Assim os volumes ficam rápidos.

### 2. Clonar

```bash
git clone https://github.com/MacielSousa/due-diligence-ia.git
cd due-diligence-ia/apps/api
```

### 3. Instalar as dependências PHP (sem PHP local)

O comando monta a **raiz do monorepo** porque a API usa o pacote `packages/risk-engine`:

```bash
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$(cd ../.. && pwd)":/app -w /app/apps/api \
  composer:2 composer install --ignore-platform-reqs --no-interaction
```

Se você já tem PHP 8.3+ e Composer, basta `composer install --ignore-platform-req=ext-mongodb`.

### 4. Configurar o ambiente

```bash
cp .env.example .env
```

Os padrões já funcionam. Principais variáveis:

| Variável | Padrão | Para quê |
|---|---|---|
| `APP_PORT` | `8080` | Porta da API |
| `WEB_PORT` | `5173` | Porta do frontend |
| `FORWARD_DB_PORT` / `FORWARD_REDIS_PORT` / `FORWARD_MONGODB_PORT` | `3307` / `6380` / `27018` | Acesso aos bancos pelo host |
| `IA_DRIVER` | `heuristico` | `heuristico`, `ollama` ou `anthropic` ([detalhes](docs/operacao.md#trocar-o-driver-de-ia)) |

### 5. Subir os containers

```bash
./vendor/bin/sail up -d
```

Na primeira vez a imagem PHP é construída (alguns minutos) e o container `web` roda `npm install`
sozinho. Acompanhe com `./vendor/bin/sail ps`.

### 6. Gerar a chave e criar o banco com dados de demonstração

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

O seed cria três construtoras **fictícias**:

| ID | Construtora | Resultado esperado (driver heurístico) |
|---|---|---|
| 1 | Horizonte Azul S.A. | **BAIXO**: todas as certidões negativas |
| 2 | Pedra Firme Ltda. | **MÉDIO**: prejuízo, protestos, atraso de obra, passivo trabalhista |
| 3 | Muralha Ltda. | **ELEVADO**: consta na Lista Suja (eliminatório), recuperação judicial |

### 7. Acessar

| O quê | Endereço |
|---|---|
| **Aplicação** | <http://localhost:5173> |
| API | <http://localhost:8080/api/fornecedores> |
| Health check | <http://localhost:8080/up> |

Roteiro de demonstração:

1. Abra <http://localhost:5173> e entre em **Construtora Muralha Ltda.**
2. Clique em **Avaliar risco**. A avaliação vai para a fila e a tela acompanha o processamento.
3. Veja o parecer **ELEVADO**: o sinal eliminatório com a evidência literal, as notas por dimensão, os
   documentos faltantes e o motivo de cada decisão.
4. Registre a **decisão do comitê** e abra a **auditoria das chamadas ao LLM** (vinda do MongoDB).

Pelo terminal, sem fila:

```bash
./vendor/bin/sail artisan risco:avaliar 3
```

### 8. (Opcional) Usar um LLM de verdade

Por padrão roda o driver **heurístico**: offline, gratuito e determinístico, ideal para demo e testes.

**Llama local e gratuito (Ollama):**

```bash
./vendor/bin/sail exec ollama ollama pull llama3.2:3b   # ~2 GB, uma vez só
bash ../../scripts/avaliar-com-llama.sh                 # avalia as 3 construtoras com o Llama
```

Para o botão da tela usar o Llama, defina `IA_DRIVER=ollama` no `.env` e rode
`./vendor/bin/sail restart queue`. Tem GPU NVIDIA? Veja
[GPU NVIDIA para o Ollama](docs/operacao.md#gpu-nvidia-para-o-ollama-wsl).

**Claude (Anthropic):**

```bash
bash ../../scripts/configurar-claude.sh   # pede a chave sem exibi-la e reinicia o worker
```

### 9. Parar

```bash
./vendor/bin/sail down      # para os containers e mantém os dados
./vendor/bin/sail down -v   # para e apaga bancos e modelo baixado
```

---

## Testes

Com os containers no ar, a partir da raiz do repositório:

```bash
bash scripts/testar-tudo.sh
```

| Suíte | Testes | O que cobre |
|---|---|---|
| Motor de risco: unitários | 35 | Score, eliminatórios, evidência, negação, piso por severidade, confiança, prompt injection, regressões do Llama |
| Módulos Laravel: unitários | 14 | CNPJ, auditoria, seleção de driver, cliente Ollama |
| Integração: API + MySQL + Redis + MongoDB | 17 | Fluxo completo das 3 construtoras, fila, auditoria, 409/422, decisão |
| Frontend: Vitest | 9 | Componentes, formatação, cliente da API |
| Frontend: type-check + build | — | `vue-tsc` + `vite build` |

Comandos por suíte em [docs/operacao.md](docs/operacao.md#testes).

## Estrutura

```
.
├── RESPOSTA.md              # resposta da questão
├── docs/                    # arquitetura, API, operação
├── scripts/                 # testes, Llama, Claude, GPU
├── packages/risk-engine/    # motor de risco (PHP puro)
└── apps/
    ├── api/                 # Laravel 13 + Sail
    │   └── modules/         # Fornecedores, Documentos, IA, Auditoria, Avaliacao
    └── web/                 # Vue 3 + Vite + TypeScript
```

## Limites da PoC

- Sem autenticação nem perfis de acesso.
- Documentos entram como texto (`.txt` ou colado). Extração de PDF/OCR e integração com fontes oficiais
  ficam para a próxima etapa.
- O driver heurístico existe para demo e testes; não substitui um LLM.
- Falta um conjunto de avaliação (evals) com empresas já classificadas por analistas para medir a
  qualidade antes de usar em decisões reais.

Próximos passos em [docs/arquitetura.md](docs/arquitetura.md#13-próximos-passos-para-produção).
