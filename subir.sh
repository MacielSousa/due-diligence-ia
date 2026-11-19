#!/usr/bin/env bash
# Sobe a aplicação inteira em containers (Laravel Sail): API, worker, Vue, MySQL, Redis e Ollama.
#
# Uso:  ./subir.sh            sobe tudo (1ª vez ou dia a dia — pode rodar quantas vezes quiser)
#       ./subir.sh status     mostra os containers e os endereços
#       ./subir.sh parar      para os containers (os dados ficam guardados nos volumes)
#
# Cada passo é pulado quando já está feito: nunca apaga o banco nem recria o modelo à toa.
set -euo pipefail

RAIZ="$(cd "$(dirname "$0")" && pwd)"
BACKEND="$RAIZ/backend"
SAIL="$BACKEND/vendor/bin/sail"
cd "$BACKEND"

passo() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
ok()    { printf '    \033[32m✔\033[0m %s\n' "$*"; }
aviso() { printf '    \033[33m!\033[0m %s\n' "$*"; }
erro()  { printf '\n\033[31m✘ %s\033[0m\n' "$*" >&2; exit 1; }

env_valor() { grep -E "^$1=" .env | tail -1 | cut -d= -f2- | tr -d '"'; }

enderecos() {
    printf '\n\033[1mPronto!\033[0m\n'
    printf '  Aplicação  http://localhost:%s\n' "$(env_valor WEB_PORT || echo 5180)"
    printf '  API        http://localhost:%s/api/terceiros\n' "$(env_valor APP_PORT || echo 8000)"
    printf '  MySQL      localhost:%s  banco=%s  usuário=%s  senha=%s\n' \
        "$(env_valor FORWARD_DB_PORT)" "$(env_valor DB_DATABASE)" "$(env_valor DB_USERNAME)" "$(env_valor DB_PASSWORD)"
    printf '  Ollama     http://localhost:%s\n' "$(env_valor FORWARD_OLLAMA_PORT)"
}

case "${1:-subir}" in
    parar)
        "$SAIL" down
        exit 0 ;;
    status)
        "$SAIL" ps
        enderecos
        exit 0 ;;
    subir) ;;
    *) erro "Opção desconhecida: $1 (use: subir | status | parar)" ;;
esac

# 1. Pré-requisitos ------------------------------------------------------------------------------
passo "Conferindo o Docker"
command -v docker >/dev/null || erro "Docker não encontrado. Instale o Docker (no Windows, dentro do WSL 2)."
docker info >/dev/null 2>&1 || erro "O Docker não está rodando. Inicie o serviço e tente de novo."
ok "Docker $(docker version --format '{{.Server.Version}}')"

# 2. Dependências PHP (vendor/) -------------------------------------------------------------------
passo "Dependências PHP"
if [ -f vendor/autoload.php ]; then
    ok "já instaladas"
elif command -v composer >/dev/null; then
    composer install --no-interaction
else
    # Sem PHP/Composer na máquina: instala usando um container do Composer.
    docker run --rm -u "$(id -u):$(id -g)" -v "$BACKEND":/app -w /app composer:2 \
        composer install --no-interaction --ignore-platform-reqs
fi

# 3. Arquivo .env --------------------------------------------------------------------------------
passo "Configuração (.env)"
if [ -f .env ]; then
    ok ".env já existe"
else
    cp .env.example .env
    ok ".env criado a partir do .env.example"
fi

# 4. Containers ----------------------------------------------------------------------------------
passo "Subindo os containers (a 1ª vez demora: imagens e npm install)"
"$SAIL" up -d

# 5. Chave da aplicação --------------------------------------------------------------------------
if [ -z "$(env_valor APP_KEY)" ]; then
    passo "Gerando a APP_KEY"
    "$SAIL" artisan key:generate --force
fi

# 6. Banco de dados ------------------------------------------------------------------------------
passo "Banco de dados (MySQL)"
for i in $(seq 1 30); do
    "$SAIL" artisan migrate --force >/dev/null 2>&1 && break
    [ "$i" = 30 ] && erro "Não consegui conectar ao MySQL. Veja: ./vendor/bin/sail logs mysql"
    sleep 2
done
ok "tabelas atualizadas (migrate)"

total=$("$SAIL" exec -T mysql mysql -u"$(env_valor DB_USERNAME)" -p"$(env_valor DB_PASSWORD)" \
    -N -e 'select count(*) from terceiros' "$(env_valor DB_DATABASE)" 2>/dev/null | tr -d '\r')
if [ "${total:-0}" != "0" ]; then
    ok "banco já tem $total terceiro(s): nada a popular"
elif [ -s database/database.sqlite ]; then
    "$SAIL" artisan db:importar-sqlite
    ok "dados importados do SQLite antigo (database/database.sqlite)"
else
    "$SAIL" artisan db:seed --force
    ok "banco vazio: dados de exemplo criados (seed)"
fi

# 7. Modelo de IA --------------------------------------------------------------------------------
passo "Modelo de IA no Ollama"
MODELO="$(env_valor OLLAMA_MODEL)"
if "$SAIL" exec -T ollama ollama list 2>/dev/null | grep -q "^${MODELO}[: ]"; then
    ok "modelo $MODELO já existe"
else
    aviso "modelo $MODELO não encontrado: baixando o llama3.2:3b (~2 GB) e criando o modelo"
    "$SAIL" exec -T ollama ollama pull llama3.2:3b
    "$RAIZ/ia/criar-modelo.sh" "$("$SAIL" ps -q ollama)"
fi

# 8. Frontend ------------------------------------------------------------------------------------
passo "Aguardando o frontend (na 1ª vez roda npm install)"
WEB="http://localhost:$(env_valor WEB_PORT || echo 5180)"
for i in $(seq 1 90); do
    [ "$(curl -s -o /dev/null -w '%{http_code}' -m 3 "$WEB/api/terceiros")" = 200 ] && break
    [ "$i" = 90 ] && erro "O frontend não respondeu em 3 min. Veja: ./vendor/bin/sail logs web"
    sleep 2
done
ok "frontend e API respondendo"

enderecos
