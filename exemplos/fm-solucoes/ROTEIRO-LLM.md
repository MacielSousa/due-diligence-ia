# Roteiro de teste com LLM (Llama) — FM Soluções Tecnologicas

> Todos os documentos desta pasta são **fictícios**, criados para testar o sistema.

## Como usar

1. Extraia o texto de cada PDF (`pdftotext 01-contrato-social.pdf -`) ou use os `.txt` equivalentes.
2. Envie cada documento ao modelo com o prompt abaixo (um documento por chamada — etapa *map*).
3. Junte os achados de todos os documentos e peça a classificação final (etapa *reduce*).
4. Compare com `achados_esperados` e `classificacao_esperada` em `dados-fm-solucoes.json`.

## Prompt por documento (map)

```
Você é analista de due diligence de fornecedores. Leia o DOCUMENTO e liste os achados de risco.
Para cada achado responda em JSON:
{"tipo": "sancao|processo_criminal|processo_trabalhista|midia_negativa|pep|debito_fiscal",
 "ano": <ano>, "envolvido": "<empresa ou sócio>", "trecho": "<cópia LITERAL do documento>",
 "eliminatorio": true|false}
Regras: não invente; o "trecho" precisa existir exatamente no documento; se a certidão for
negativa (nada consta), devolva []. Responda só o JSON.

DOCUMENTO:
<<cole aqui o texto do documento>>
```

## Prompt final (reduce)

```
Com base nestes achados (JSON), classifique o risco de contratação da empresa em BAIXO, MÉDIO ou
ELEVADO e justifique em até 5 linhas, citando os trechos. Sanção vigente de impedimento de licitar
é eliminatória.
ACHADOS: <<cole aqui a lista>>
```

## Resultado esperado

- 02 e 03 (certidões negativas): **nenhum achado**.
- 04: processo criminal 2026 (ação penal por fraude em licitação, em andamento).
- 05: sanção vigente 2026–2029 (impedimento de licitar) → **eliminatória**.
- 06: mídia negativa 2025 sobre o sócio administrador.
- 01: sócio administrador é **PEP**.
- Classificação final esperada: **ELEVADO**.
