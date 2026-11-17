# Questão 6 — Como usar um LLM para resumir documentos e classificar o risco de contratação de uma empresa

## Resposta curta

Eu **não** pediria ao LLM "leia tudo e diga se o risco é baixo, médio ou alto". Eu usaria o LLM
para o que ele faz bem — **ler texto não estruturado, resumir e extrair evidências** — e deixaria a
**decisão final em regras determinísticas, auditáveis e com revisão humana**. A solução é um pipeline
em etapas:

```
Documentos ──► 1. Ingestão ──► 2. Resumo + extração por documento (LLM, saída JSON com schema)
                                        │
                                        ▼
               3. Regras eliminatórias (código)  ──► "knock-out" (ex.: Lista Suja → ELEVADO)
                                        │
                                        ▼
               4. Avaliação por dimensão (LLM, nota 0–100 + justificativa citando evidências)
                                        │
                                        ▼
               5. Score ponderado e classificação (código) ──► BAIXO / MÉDIO / ELEVADO
                                        │
                                        ▼
               6. Parecer + rastreabilidade + revisão humana obrigatória quando necessário
```

## 1. Ingestão dos documentos

Fontes típicas para contratar uma construtora:

| Dimensão | Documentos / fontes |
|---|---|
| **Financeira** (risco de calote) | Balanços e DREs, certidão de falência/recuperação judicial, protestos, Serasa/score de crédito |
| **Técnica / entrega** | Atestados de capacidade técnica, CAT no CREA, histórico de obras, atrasos e multas contratuais |
| **Trabalhista / ESG** (trabalho escravo) | Lista Suja do MTE, CNDT (certidão negativa de débitos trabalhistas), autuações, embargos ambientais (IBAMA) |
| **Jurídica / idoneidade** | Certidões negativas (federal, FGTS, estadual, municipal), CEIS/CNEP (empresas inidôneas e punidas), processos judiciais, situação do CNPJ, quadro societário |
| **Reputacional** | Notícias, Reclame Aqui, envolvimento em operações policiais / Lei Anticorrupção |

Os documentos (PDF, HTML, texto) são convertidos para texto (OCR quando necessário), recebem
metadados (tipo, fonte, data de emissão, validade) e ficam armazenados com **hash**, para que todo
parecer aponte exatamente quais versões dos documentos foram usadas.

## 2. Resumo e extração por documento (etapa *map*)

Cada documento passa **individualmente** pelo LLM (padrão *map-reduce*: evita estourar o contexto e
deixa cada resumo rastreável). O prompt define o papel ("analista de due diligence de fornecedores"),
o tipo do documento e exige **saída estruturada com JSON Schema** (no Claude: `output_config.format`),
por exemplo:

```json
{
  "resumo": "Certidão positiva de débitos trabalhistas com 3 processos em execução...",
  "achados": [
    {
      "dimensao": "trabalhista",
      "severidade": "alta",
      "descricao": "Débito trabalhista em execução de R$ 1,2 mi",
      "evidencia": "trecho literal copiado do documento"
    }
  ],
  "sinais_eliminatorios": ["lista_suja_trabalho_escravo"],
  "data_documento": "2026-09-30"
}
```

Pontos importantes:

- **Evidência literal obrigatória**: cada achado precisa citar o trecho do documento. O sistema
  **verifica em código** se o trecho existe no texto original; achado sem evidência é descartado.
  Isso é a principal defesa contra alucinação.
- **Temperatura baixa / esforço adequado e schema rígido** para respostas consistentes.
- **Proteção contra prompt injection**: o documento entra como *dado* (delimitado), e o prompt deixa
  claro que instruções dentro do documento devem ser ignoradas.
- Documentos muito longos são quebrados em pedaços (*chunking*) e os resumos parciais são consolidados.

## 3. Regras eliminatórias (determinísticas, sem LLM)

Alguns fatos **não podem depender da "opinião" do modelo**. São regras em código que, se disparadas,
classificam automaticamente como **ELEVADO**:

- Empresa ou sócio na **Lista Suja do trabalho escravo**;
- Empresa no **CEIS/CNEP** (inidônea/suspensa para contratar);
- **Falência** ou CNPJ inapto/baixado;
- Condenação por corrupção / Lei Anticorrupção.

## 4. Avaliação por dimensão (etapa *reduce*)

Um segundo prompt recebe **somente os resumos e achados validados** (não os documentos brutos) e
devolve, para cada dimensão, uma nota de risco de 0 a 100, uma justificativa e os IDs dos achados que a
sustentam — também em JSON com schema. Pedir uma nota **por dimensão** é muito mais confiável e
explicável do que pedir uma classificação global.

## 5. Score e classificação (código)

O **cálculo final é determinístico** e configurável pelo time de compras/compliance:

```
score = Σ (nota_dimensão × peso_dimensão)
pesos (exemplo): financeira 30%, técnica 25%, trabalhista 20%, jurídica 15%, reputacional 10%

score < 35            → BAIXO
35 ≤ score < 65       → MÉDIO
score ≥ 65            → ELEVADO
qualquer knock-out    → ELEVADO
qualquer dimensão ≥ 80 → no mínimo MÉDIO (uma dimensão crítica não pode ser "diluída")
```

Também é calculada a **confiança**: documentos obrigatórios ausentes ou vencidos (ex.: certidão com
mais de 90 dias) reduzem a confiança e **forçam revisão humana**.

## 6. Parecer e governança

O resultado é um parecer com: classificação, score, nota por dimensão, resumo executivo, lista de
achados com evidência e link para o documento de origem, documentos faltantes e recomendação
(ex.: "contratar com garantia de execução de 10% e retenção contratual").

Governança:

- **Humano no loop**: o LLM recomenda; a decisão de contratar R$ bilhões é de um comitê. Risco
  ELEVADO, MÉDIO ou baixa confiança exigem aprovação humana.
- **Auditoria**: grava prompt, versão do modelo, documentos (hash), respostas e o score — o parecer
  pode ser reproduzido e explicado (importante para LGPD e compliance).
- **Avaliação contínua (evals)**: um conjunto de empresas já classificadas por analistas é usado para
  medir acurácia a cada mudança de prompt ou modelo, especialmente o recall de casos ELEVADOS
  (um falso "BAIXO" é o erro mais caro).
- **Monitoramento contínuo**: reavaliar periodicamente durante a obra (novas certidões, notícias),
  não apenas na contratação.

## Por que essa arquitetura

| Problema | Como é tratado |
|---|---|
| Alucinação | Evidência literal verificada em código; achado sem evidência é descartado |
| Explicabilidade | Nota por dimensão + achados citados + score em fórmula aberta |
| Consistência | Saída JSON com schema; decisão final em código |
| Fatos eliminatórios | Regras determinísticas, não dependem do LLM |
| Contexto grande | Map-reduce: resume documento a documento, depois consolida |
| Custo | Resumos por documento podem usar um modelo menor; cache do prompt de sistema |
| Responsabilidade | Revisão humana obrigatória para risco médio/elevado ou baixa confiança |

## Prova de conceito

Este repositório implementa essa arquitetura como um **monorepo** conteinerizado com Laravel Sail:

- `packages/risk-engine` — motor de risco puro em PHP (pipeline, regras eliminatórias, score),
  independente de framework e testado com um LLM falso;
- `apps/api` — Laravel 13 organizado em módulos (Fornecedores, Documentos, IA, Auditoria, Avaliacao),
  com MySQL (dados relacionais), Redis (fila das avaliações) e MongoDB (texto dos documentos e
  auditoria das chamadas ao LLM), adaptador do Claude (Anthropic PHP SDK) e um driver heurístico
  para rodar a demonstração sem chave de API;
- `apps/web` — Vue 3 + Vite + TypeScript para cadastrar fornecedores, anexar documentos, acompanhar
  a avaliação e registrar a decisão do comitê.

Testes: unitários (motor, módulos e componentes Vue) e de integração (API + MySQL + Redis + MongoDB
reais nos containers).

Veja o [README](README.md) para executar.
