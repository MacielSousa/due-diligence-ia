# API REST

Base: `http://localhost:8080/api`. Pelo frontend, a mesma API responde em `http://localhost:5173/api`
(via proxy do Vite).

- Envie `Accept: application/json` para receber erros em JSON.
- Respostas de sucesso vêm dentro de `data`.
- Erros de validação retornam **422** com `message` e `errors`.

| Método | Rota | Descrição | Sucesso |
|---|---|---|---|
| GET | `/fornecedores` | Lista (paginada, 20 por página) | 200 |
| POST | `/fornecedores` | Cadastra | 201 |
| GET | `/fornecedores/{id}` | Detalhe | 200 |
| GET | `/documentos/tipos` | Tipos aceitos, obrigatórios e validade | 200 |
| GET | `/fornecedores/{id}/documentos` | Documentos do fornecedor | 200 |
| POST | `/fornecedores/{id}/documentos` | Anexa documento (texto ou arquivo `.txt`) | 201 |
| GET | `/fornecedores/{id}/avaliacoes` | Histórico de avaliações | 200 |
| POST | `/fornecedores/{id}/avaliacoes` | Solicita avaliação (vai para a fila) | 202 |
| GET | `/avaliacoes/{id}` | Parecer completo | 200 |
| POST | `/avaliacoes/{id}/decisao` | Decisão do comitê | 200 |
| GET | `/avaliacoes/{id}/auditoria` | Chamadas ao LLM (MongoDB) | 200 |

---

## Fornecedores

### Cadastrar

```bash
curl -X POST http://localhost:8080/api/fornecedores \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"razao_social":"Construtora Exemplo S.A.","cnpj":"11.222.333/0001-81","valor_contrato_estimado":1500000000}'
```

| Campo | Regra |
|---|---|
| `razao_social` | obrigatório, até 255 caracteres |
| `cnpj` | obrigatório, dígitos verificadores válidos, único; aceita com ou sem máscara |
| `valor_contrato_estimado` | opcional, numérico ≥ 0 |
| `segmento`, `observacoes` | opcionais |

Resposta **201**:

```json
{
  "data": {
    "id": 1,
    "razao_social": "Construtora Exemplo S.A.",
    "cnpj": "11222333000181",
    "cnpj_formatado": "11.222.333/0001-81",
    "segmento": "construcao_civil",
    "valor_contrato_estimado": "1500000000.00",
    "observacoes": null,
    "criado_em": "2026-11-17T21:46:11+00:00"
  }
}
```

Erro **422**:

```json
{ "message": "CNPJ inválido.", "errors": { "cnpj": ["CNPJ inválido."] } }
```

---

## Documentos

### Tipos aceitos

`GET /documentos/tipos` retorna `valor`, `rotulo`, `obrigatorio` e `validade_dias`. Valores:
`cartao_cnpj`, `certidao_federal`, `cndt`, `lista_suja`, `ceis_cnep`, `balanco`, `atestado_tecnico`,
`processos_judiciais`, `noticias`, `outro`.

### Anexar texto

```bash
curl -X POST http://localhost:8080/api/fornecedores/1/documentos \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"tipo":"cndt","titulo":"CNDT","emitido_em":"2026-11-10","conteudo":"Certidão negativa de débitos trabalhistas.\nNada consta."}'
```

### Anexar arquivo `.txt`

```bash
curl -X POST http://localhost:8080/api/fornecedores/1/documentos \
  -H 'Accept: application/json' \
  -F tipo=balanco -F titulo='Balanço 2025' -F arquivo=@balanco.txt
```

| Campo | Regra |
|---|---|
| `tipo` | obrigatório, um dos valores acima |
| `titulo` | obrigatório |
| `emitido_em` | opcional, data ≤ hoje; usada para checar validade |
| `conteudo` **ou** `arquivo` | um dos dois; texto até 200 mil caracteres ou `.txt` até 2 MB |

O texto vai para o MongoDB. A resposta traz os metadados e o `sha256` do conteúdo.

---

## Avaliações

### Solicitar

```bash
curl -X POST http://localhost:8080/api/fornecedores/1/avaliacoes -H 'Accept: application/json'
```

Resposta **202** com `status: "pendente"`. O worker processa em segundo plano; consulte
`GET /avaliacoes/{id}` até `status` virar `concluida` ou `falhou`.

| Erro | Quando |
|---|---|
| 409 | Já existe avaliação `pendente`/`processando` para o fornecedor |
| 422 | Fornecedor sem documentos |

### Status

| `status` | Significado |
|---|---|
| `pendente` | Na fila |
| `processando` | Worker lendo documentos e consultando o LLM |
| `concluida` | Parecer pronto |
| `falhou` | Erro do LLM ou da infraestrutura; ver `erro`. Encaminhar para análise manual |

### Parecer

`GET /avaliacoes/{id}` (resumido):

```json
{
  "data": {
    "id": 1,
    "status": "concluida",
    "nivel": "elevado",
    "nivel_rotulo": "ELEVADO",
    "score": 60,
    "confianca": 0.68,
    "requer_revisao": true,
    "resultado": {
      "classificacao": "elevado",
      "score": 60,
      "confianca": 0.68,
      "requer_revisao_humana": true,
      "resumo_executivo": "...",
      "recomendacao": "Não contratar sem parecer do comitê de compliance; buscar fornecedor alternativo.",
      "motivos": [
        "Score ponderado 60.0 → MÉDIO.",
        "Eliminatório: Consta na Lista Suja do trabalho escravo.",
        "Documentos obrigatórios ausentes: certidao_federal, cndt, ceis_cnep, atestado_tecnico.",
        "Risco ELEVADO: aprovação do comitê de compliance obrigatória."
      ],
      "dimensoes": [
        { "dimensao": "trabalhista", "rotulo": "Trabalhista / ESG", "nota": 95,
          "justificativa": "Trabalho análogo à escravidão; Acidente de trabalho grave.", "achados": ["doc-12-a2"] }
      ],
      "sinais_eliminatorios": [
        { "tipo": "lista_suja_trabalho_escravo", "descricao": "Consta na Lista Suja do trabalho escravo",
          "documento_id": "doc-12", "evidencia": "Resultado: empregador incluído após fiscalização..." }
      ],
      "documentos_faltantes": ["certidao_federal", "cndt", "ceis_cnep", "atestado_tecnico"],
      "documentos_vencidos": [],
      "analises_documentos": [
        { "documento_id": "doc-12", "resumo": "...", "achados": [], "itens_descartados_sem_evidencia": 0 }
      ]
    },
    "documentos_usados": [{ "id": 12, "tipo": "lista_suja", "sha256": "..." }]
  }
}
```

### Decisão do comitê

```bash
curl -X POST http://localhost:8080/api/avaliacoes/1/decisao \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"decisao":"aprovado_com_ressalvas","decidido_por":"Comitê de Compliance","justificativa":"Seguro-garantia de 10% e retenção contratual."}'
```

`decisao`: `aprovado`, `aprovado_com_ressalvas` ou `reprovado`. A justificativa precisa ter pelo menos
10 caracteres. Só é aceita em avaliações `concluida`.

### Auditoria

`GET /avaliacoes/{id}/auditoria` lista as chamadas ao LLM daquela avaliação, na ordem: uma por documento
(`etapa: analise_documento`) e uma de consolidação (`etapa: consolidacao`). Cada item traz `driver`,
`modelo`, `duracao_ms`, `tokens_entrada`, `tokens_saida`, `erro`, `prompt` e `resposta`.

---

## Linha de comando

Avaliação síncrona, sem passar pela fila, com saída formatada no terminal:

```bash
./vendor/bin/sail artisan risco:avaliar {fornecedor_id}
```
