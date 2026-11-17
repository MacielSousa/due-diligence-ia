# Operação

Como trocar o modelo de IA, usar a GPU, rodar os testes e resolver os problemas mais comuns.
Os comandos com `./vendor/bin/sail` são executados em `apps/api`, dentro do WSL.

## Trocar o driver de IA

O driver é definido por `IA_DRIVER` no `apps/api/.env`. Depois de trocar, reinicie o worker,
porque ele mantém a configuração em memória:

```bash
./vendor/bin/sail artisan config:clear
./vendor/bin/sail restart queue
```

### `heuristico` (padrão)

Não precisa de nada. É rápido, gratuito e determinístico. Usa palavras-chave, então **não** representa a
qualidade de um LLM. Serve para demo, desenvolvimento e testes.

### `ollama` (Llama local, gratuito)

1. Baixe o modelo uma vez (cerca de 2 GB, fica no volume `sail-ollama`):

   ```bash
   ./vendor/bin/sail exec ollama ollama pull llama3.2:3b
   ```

2. No `.env`, defina `IA_DRIVER=ollama` e reinicie o worker.

Para testar sem mexer no `.env` (avalia as construtoras 1, 2 e 3 pelo terminal):

```bash
bash scripts/avaliar-com-llama.sh
```

Variáveis: `OLLAMA_MODELO` (padrão `llama3.2:3b`), `OLLAMA_URL`, `OLLAMA_TIMEOUT`, `OLLAMA_CONTEXTO`.
Outros modelos: `./vendor/bin/sail exec ollama ollama pull <modelo>` e ajuste `OLLAMA_MODELO`.

> Modelos pequenos às vezes parafraseiam em vez de citar. Nesse caso o `EvidenceVerifier` descarta o
> achado e a confiança cai. É a proteção funcionando, não um defeito.

### `anthropic` (Claude)

```bash
bash scripts/configurar-claude.sh
```

O script pede a chave **sem exibi-la**, grava no `.env` (que não vai para o git), define
`IA_DRIVER=anthropic` e reinicia o worker. Cada avaliação faz uma chamada por documento mais uma de
consolidação, cobradas na conta da Anthropic.

Variáveis: `IA_MODELO` (padrão `claude-opus-5-5`), `IA_ESFORCO` (`low` a `max`), `IA_MAX_TOKENS`,
`IA_TIMEOUT`.

## GPU NVIDIA para o Ollama (WSL)

Sem GPU, o Ollama roda na CPU (funciona, mas cada avaliação leva minutos).

Pré-requisitos:

1. Driver NVIDIA recente no Windows (com suporte a WSL). Confira com `nvidia-smi` no PowerShell.
2. Depois de atualizar o driver, rode `wsl --shutdown` no PowerShell e abra o WSL de novo.

Então, no WSL:

```bash
bash scripts/habilitar-gpu-wsl.sh
```

O script confere a GPU no WSL, instala o **NVIDIA Container Toolkit** (repositório oficial, pede sudo),
configura o Docker, ativa `COMPOSE_FILE=compose.yaml:compose.gpu.yaml` no `.env` e recria o Ollama.

Conferir:

```bash
./vendor/bin/sail exec ollama nvidia-smi
./vendor/bin/sail exec ollama ollama ps     # coluna PROCESSOR mostra o % em GPU
```

Com 4 GB de VRAM (ex.: GTX 1650), o `llama3.2:3b` com contexto de 8192 tokens roda cerca de 80% na GPU.
Para caber 100%, reduza `OLLAMA_CONTEXTO` (ex.: 4096) ou use `llama3.2:1b`.

## Testes

Tudo de uma vez (com os containers no ar):

```bash
bash scripts/testar-tudo.sh
```

Ou por suíte:

```bash
./vendor/bin/sail artisan test --testsuite=Unit          # módulos, sem banco
./vendor/bin/sail artisan test --testsuite=Integration   # MySQL + Redis + MongoDB reais
./vendor/bin/sail exec web npm test                      # Vitest
./vendor/bin/sail exec web npm run build                 # type-check + build
```

Motor de risco isolado (precisa de PHP 8.2+ na máquina, ou rode dentro do container):

```bash
cd packages/risk-engine && composer install && vendor/bin/phpunit
```

Os testes de integração usam o banco `testing` (criado automaticamente pelo Sail) e o banco MongoDB
`testing`. Os dados de desenvolvimento não são afetados.

## Comandos úteis

| Para | Comando |
|---|---|
| Ver os containers | `./vendor/bin/sail ps` |
| Logs da API | `./vendor/bin/sail logs -f laravel.test` |
| Logs do worker | `./vendor/bin/sail logs -f queue` |
| Logs do Vue | `./vendor/bin/sail logs -f web` |
| Recriar banco com dados de demo | `./vendor/bin/sail artisan migrate:fresh --seed` |
| Avaliar pelo terminal | `./vendor/bin/sail artisan risco:avaliar 1` |
| Shell no container | `./vendor/bin/sail shell` |
| Parar tudo | `./vendor/bin/sail down` |
| Parar e apagar os dados | `./vendor/bin/sail down -v` |

## Problemas comuns

| Sintoma | Causa provável | Solução |
|---|---|---|
| Porta 8080, 5173, 3307, 6380, 27018 ou 11434 em uso | Outro projeto usando a porta | Troque `APP_PORT`, `WEB_PORT`, `FORWARD_*` no `.env` |
| Avaliação fica em "Na fila" para sempre | Worker parado | `./vendor/bin/sail ps`; `./vendor/bin/sail restart queue` |
| Avaliação `falhou` com "Ollama indisponível" | Container `ollama` parado | `./vendor/bin/sail up -d ollama` |
| `model "llama3.2:3b" not found` | Modelo não baixado | `./vendor/bin/sail exec ollama ollama pull llama3.2:3b` |
| `IA_DRIVER=anthropic exige ANTHROPIC_API_KEY` | Chave vazia | `bash scripts/configurar-claude.sh` |
| Troquei o `.env` e nada mudou | Worker com config antiga | `config:clear` e `restart queue` |
| `could not select device driver "nvidia"` | Toolkit NVIDIA não instalado | `bash scripts/habilitar-gpu-wsl.sh`, ou remova `COMPOSE_FILE` do `.env` para rodar na CPU |
| `nvidia-smi` não funciona no WSL depois de atualizar o driver | WSL com a sessão antiga | `wsl --shutdown` no PowerShell |
| `Class "Modules\..." not found` | Autoload desatualizado | `./vendor/bin/sail composer dump-autoload` |
| Avisos `WWWUSER variable is not set` | Uso de `docker compose` direto | Inofensivo; prefira `./vendor/bin/sail` |
