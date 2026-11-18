"""
Gera o dataset de treino (fine-tuning) do modelo due-diligence.

Cada exemplo é uma conversa no formato de chat:
  system    -> mesmas instruções do ia/Modelfile (fonte única)
  user      -> texto de UM documento sintético (contrato, certidão, sanção, notícia...)
  assistant -> o JSON de achados esperado (o "gabarito")

Todos os dados são FICTÍCIOS e aleatórios. Os documentos da FM Soluções
(exemplos/fm-solucoes) NÃO entram aqui: ficam como teste final.

Uso: python gerar_dataset.py [quantidade] [semente]
Saída: dados/treino.jsonl e dados/validacao.jsonl
"""

import json
import random
import re
import sys
from pathlib import Path

AQUI = Path(__file__).resolve().parent
QTD = int(sys.argv[1]) if len(sys.argv) > 1 else 600
rnd = random.Random(int(sys.argv[2]) if len(sys.argv) > 2 else 42)

# --------------------------------------------------------------------------- system prompt
modelfile = (AQUI.parent / "Modelfile").read_text(encoding="utf-8")
SYSTEM = re.search(r'SYSTEM """(.*?)"""', modelfile, re.S).group(1).strip()

# --------------------------------------------------------------------------- dados aleatórios
PREFIXOS = ["Alfa", "Ômega", "Delta", "Sigma", "Atlas", "Vértice", "Horizonte", "Prisma", "Nexo", "Aurora",
            "Pilar", "Rota", "Quartzo", "Cobalto", "Trilha", "Lume", "Fênix", "Zênite", "Orion", "Vetor"]
RAMOS = ["Engenharia", "Construções", "Tecnologia", "Serviços", "Comércio", "Logística", "Consultoria",
         "Sistemas", "Saneamento", "Limpeza Urbana", "Alimentos", "Transportes", "Informática", "Obras"]
SUFIXOS = ["Ltda", "Ltda", "S.A.", "Eireli", "ME", "EPP"]
NOMES = ["Ana", "Bruno", "Carla", "Diego", "Elisa", "Fábio", "Gabriela", "Heitor", "Isabela", "João",
         "Karina", "Lucas", "Marina", "Nelson", "Olívia", "Paulo", "Renata", "Sérgio", "Tatiane", "Vitor"]
SOBRENOMES = ["Almeida", "Barros", "Cardoso", "Duarte", "Esteves", "Freitas", "Gomes", "Henriques",
              "Lacerda", "Moreira", "Nogueira", "Pacheco", "Queiroz", "Rezende", "Siqueira", "Tavares"]
CIDADES = ["Vila Exemplo", "Cidade Modelo", "Porto Fictício", "Serra Exemplo", "Campo Teste", "Lagoa Modelo"]
ORGAOS = ["Prefeitura Municipal de {c}", "Governo do Estado Exemplo", "Câmara Municipal de {c}",
          "Secretaria de Saúde de {c}", "Autarquia de Águas de {c}"]
SANCOES = ["Impedimento de licitar e contratar com a Administração Pública",
           "Suspensão temporária de participação em licitação",
           "Declaração de inidoneidade para licitar ou contratar com a Administração Pública"]
SANCOES_NAO_IMPEDITIVAS = ["Advertência", "Multa administrativa"]
CRIMES = ["fraude em licitação (art. 337-L do Código Penal)", "corrupção ativa (art. 333 do Código Penal)",
          "frustração do caráter competitivo de licitação (art. 337-F do Código Penal)",
          "lavagem de dinheiro (Lei nº 9.613/1998)", "falsidade ideológica (art. 299 do Código Penal)"]
CARGOS_PEP = ["Secretário Municipal de Obras", "vereador", "Deputado Estadual", "Prefeito",
              "Diretor de autarquia estadual", "Secretária Municipal de Saúde"]
NOTICIAS_NEG = [
    ("{e} é investigada por superfaturamento em obra pública", "O Ministério Público apura superfaturamento de R$ {v} mil em contrato da {e} com a prefeitura."),
    ("Operação policial cumpre mandados na sede da {e}", "Agentes cumpriram mandados de busca na sede da {e} por suspeita de fraude em contratos."),
    ("{p} é denunciado por desvio de recursos", "O empresário {p}, sócio da {e}, foi denunciado por desviar recursos de convênios públicos."),
    ("{e} deixa obra inacabada e abandona canteiro", "A {e} abandonou a obra da escola municipal com apenas 40% concluídos, segundo a fiscalização."),
]
NOTICIAS_NEUTRAS = [
    ("{e} inaugura nova sede", "A {e} inaugurou sua nova sede com capacidade para 200 colaboradores."),
    ("{e} recebe certificação de qualidade", "A empresa {e} obteve certificação ISO 9001 após auditoria externa."),
    ("{e} patrocina evento esportivo regional", "A {e} anunciou o patrocínio da corrida de rua da cidade."),
]


def dv(base: list[int], pesos: list[int]) -> int:
    resto = sum(d * p for d, p in zip(base, pesos)) % 11
    return 0 if resto < 2 else 11 - resto


def cnpj() -> str:
    b = [rnd.randint(0, 9) for _ in range(8)] + [0, 0, 0, 1]
    b.append(dv(b, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]))
    b.append(dv(b, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]))
    s = "".join(map(str, b))
    return f"{s[:2]}.{s[2:5]}.{s[5:8]}/{s[8:12]}-{s[12:]}"


def cpf() -> str:
    b = [rnd.randint(0, 9) for _ in range(9)]
    for t in (9, 10):
        r = (sum(d * ((t + 1) - i) for i, d in enumerate(b)) * 10) % 11
        b.append(0 if r == 10 else r)
    s = "".join(map(str, b))
    return f"{s[:3]}.{s[3:6]}.{s[6:9]}-{s[9:]}"


def empresa() -> str:
    return f"{rnd.choice(PREFIXOS)} {rnd.choice(RAMOS)} {rnd.choice(SUFIXOS)}"


def pessoa() -> str:
    return f"{rnd.choice(NOMES)} {rnd.choice(SOBRENOMES)} {rnd.choice(SOBRENOMES)}"


def data(ano: int) -> str:
    return f"{rnd.randint(1, 28):02d}/{rnd.randint(1, 12):02d}/{ano}"


def achado(tipo, ano, envolvido, trecho, eliminatorio=False):
    return {"tipo": tipo, "ano": ano, "envolvido": envolvido, "trecho": trecho, "eliminatorio": eliminatorio}


def rodape():
    return rnd.choice(["", "", "DOCUMENTO FICTÍCIO - GERADO PARA TESTE - SEM VALOR LEGAL\n", "Página 1 de 1\n"])


# --------------------------------------------------------------------------- tipos de documento
def certidao_debitos(e, doc):
    if rnd.random() < 0.7:
        texto = (f"CERTIDÃO NEGATIVA DE DÉBITOS TRIBUTÁRIOS\nContribuinte: {e} - CNPJ {doc}\n"
                 f"Certifica-se que NÃO CONSTAM pendências em nome do contribuinte acima identificado, "
                 f"nem inscrições em dívida ativa.\nEmitida em {data(2026)}.\n")
        return texto, []
    ano = rnd.randint(2019, 2026)
    valor = f"{rnd.randint(10, 900)}.{rnd.randint(100, 999)},{rnd.randint(10, 99)}"
    trecho = f"Inscrição em dívida ativa nº {rnd.randint(100000, 999999)}/{ano} - valor de R$ {valor}"
    texto = (f"CERTIDÃO POSITIVA DE DÉBITOS TRIBUTÁRIOS\nContribuinte: {e} - CNPJ {doc}\n"
             f"Certifica-se que CONSTAM as seguintes pendências:\n{trecho}\nSituação: não parcelada.\n")
    return texto, [achado("debito_fiscal", ano, e, trecho)]


def certidao_trabalhista(e, doc):
    if rnd.random() < 0.65:
        texto = (f"CERTIDÃO NEGATIVA DE DÉBITOS TRABALHISTAS\nInteressado: {e} - CNPJ {doc}\n"
                 f"Certifica-se que {e} NÃO CONSTA do banco nacional de devedores trabalhistas.\n"
                 f"Expedição: {data(2026)}. Validade: 180 dias.\n")
        return texto, []
    ano = rnd.randint(2019, 2026)
    proc = f"{rnd.randint(1000000, 9999999)}-{rnd.randint(10, 99)}.{ano}.5.{rnd.randint(1, 24):02d}.{rnd.randint(1000, 9999)}"
    trecho = f"Processo nº {proc} - débito de R$ {rnd.randint(5, 300)}.{rnd.randint(100, 999)},00 em fase de execução"
    texto = (f"CERTIDÃO DE DÉBITOS TRABALHISTAS\nInteressado: {e} - CNPJ {doc}\n"
             f"Certifica-se que CONSTA do banco de devedores trabalhistas o seguinte registro:\n{trecho}, desde {data(ano)}.\n")
    return texto, [achado("processo_trabalhista", ano, e, trecho)]


def certidao_criminal(e, doc):
    socio = pessoa()
    if rnd.random() < 0.5:
        texto = (f"CERTIDÃO DE DISTRIBUIÇÃO DE AÇÕES CRIMINAIS\nPesquisado: {e} - CNPJ {doc} e seus administradores.\n"
                 f"Resultado: NADA CONSTA nos últimos 10 anos.\nEmitida em {data(2026)}.\n")
        return texto, []
    ano = rnd.randint(2018, 2026)
    proc = f"Ação Penal nº {rnd.randint(1000000, 9999999)}-{rnd.randint(10, 99)}.{ano}.8.26.{rnd.randint(1000, 9999)}"
    trecho = f"Processo: {proc}"
    texto = (f"CERTIDÃO DE DISTRIBUIÇÃO DE AÇÕES CRIMINAIS\nPesquisado: {e} - CNPJ {doc}\n"
             f"Resultado: CONSTAM registros (certidão POSITIVA)\n{trecho}\n"
             f"Distribuição: {data(ano)} - {rnd.randint(1, 5)}ª Vara Criminal de {rnd.choice(CIDADES)}\n"
             f"Réu: {socio}, sócio administrador.\nAssunto: {rnd.choice(CRIMES)}.\n"
             f"Situação: {rnd.choice(['em andamento', 'denúncia recebida', 'aguardando sentença'])}.\n")
    # distrator: frase negativa sobre OUTRAS partes num documento positivo
    if rnd.random() < 0.6:
        texto += rnd.choice(["Não constam outros feitos criminais em nome dos pesquisados.\n",
                             f"Nada consta em nome de {pessoa()}, também pesquisado.\n"])
    return texto, [achado("processo_criminal", ano, socio, trecho)]


def consulta_sancoes(e, doc):
    sorteio = rnd.random()
    cabecalho = f"CONSULTA A CADASTRO DE EMPRESAS SANCIONADAS\nParâmetro: CNPJ {doc} ({e})\n"
    if sorteio < 0.4:
        return cabecalho + f"Resultado: nenhuma sanção encontrada.\nConsulta em {data(2026)}.\n", []
    ano = rnd.randint(2020, 2026)
    orgao = rnd.choice(ORGAOS).format(c=rnd.choice(CIDADES))
    if sorteio < 0.8:
        tipo_sancao = rnd.choice(SANCOES)
        trecho = f"Tipo de sanção: {tipo_sancao}"
        texto = (cabecalho + f"Resultado: 1 sanção VIGENTE encontrada\n{trecho}\nÓrgão sancionador: {orgao}\n"
                 f"Data de início: {data(ano)}\nData de término: {data(ano + rnd.randint(1, 5))}\n")
        if rnd.random() < 0.6:  # distrator: sócios sem sanção
            texto += f"Sócios pesquisados:\n{pessoa()}: nenhuma sanção individual registrada.\n"
        return texto, [achado("sancao", ano, e, trecho, True)]
    # sanção que não impede de licitar: é achado, mas NÃO eliminatório
    tipo_sancao = rnd.choice(SANCOES_NAO_IMPEDITIVAS)
    trecho = f"Tipo de sanção: {tipo_sancao}"
    texto = (cabecalho + f"Resultado: 1 sanção registrada\n{trecho}\nÓrgão sancionador: {orgao}\n"
             f"Data da aplicação: {data(ano)}\n")
    return texto, [achado("sancao", ano, e, trecho, False)]


def contrato_social(e, doc):
    adm, outro = pessoa(), pessoa()
    pct = rnd.choice([50, 60, 70, 80])
    texto = (f"CONTRATO SOCIAL - {e}\nCNPJ {doc}\n"
             f"Cláusula 4ª - O capital social é de R$ {rnd.randint(20, 900)}.000,00, dividido entre "
             f"{adm} (CPF {cpf()}, {pct}%) e {outro} (CPF {cpf()}, {100 - pct}%).\n"
             f"Cláusula 5ª - A administração cabe ao sócio {adm}.\n")
    if rnd.random() < 0.5:
        return texto + "Cláusula 6ª - Os sócios declaram não estar impedidos de exercer a administração.\n", []
    fim = rnd.randint(2008, 2025)
    cargo = rnd.choice(CARGOS_PEP)
    trecho = f"exerceu o cargo de {cargo} de {rnd.choice(CIDADES)} entre {fim - 4} e {fim}"
    return texto + f"Declara o administrador que {trecho}.\n", [achado("pep", fim, adm, trecho)]


def clipping(e, doc):
    socio = pessoa()
    linhas, achados = [f"CLIPPING DE MÍDIA - {e}\n"], []
    for i in range(rnd.randint(1, 3)):
        ano = rnd.randint(2019, 2026)
        if rnd.random() < 0.5:
            titulo, trecho = rnd.choice(NOTICIAS_NEG)
            trecho = trecho.format(e=e, p=socio, v=rnd.randint(100, 900))
            achados.append(achado("midia_negativa", ano, e if "{p}" not in titulo else socio, trecho))
        else:
            titulo, trecho = rnd.choice(NOTICIAS_NEUTRAS)
            trecho = trecho.format(e=e)
        linhas.append(f"Matéria {i + 1} - Jornal Exemplo, {data(ano)}\nTítulo: \"{titulo.format(e=e, p=socio)}\"\nTrecho: \"{trecho}\"\n")
    # notícias repetidas do mesmo fato não podem gerar 2 achados iguais
    unicos = {(a["tipo"], a["trecho"]): a for a in achados}
    return "".join(linhas), list(unicos.values())


GERADORES = [certidao_debitos, certidao_trabalhista, certidao_criminal, consulta_sancoes, contrato_social, clipping]


def exemplo():
    e, doc = empresa(), cnpj()
    texto, achados = rnd.choice(GERADORES)(e, doc)
    texto = rodape() + texto + rodape()
    resposta = json.dumps({"achados": achados}, ensure_ascii=False)
    return {"messages": [
        {"role": "system", "content": SYSTEM},
        {"role": "user", "content": texto.strip()},
        {"role": "assistant", "content": resposta},
    ]}


def main():
    saida = AQUI / "dados"
    saida.mkdir(exist_ok=True)
    exemplos = [exemplo() for _ in range(QTD)]
    corte = int(len(exemplos) * 0.9)
    for nome, parte in (("treino", exemplos[:corte]), ("validacao", exemplos[corte:])):
        with open(saida / f"{nome}.jsonl", "w", encoding="utf-8") as f:
            for ex in parte:
                f.write(json.dumps(ex, ensure_ascii=False) + "\n")
    vazios = sum(1 for ex in exemplos if ex["messages"][2]["content"] == '{"achados": []}')
    print(f"{len(exemplos)} exemplos ({corte} treino, {len(exemplos) - corte} validação); "
          f"{vazios} sem achados (certidões negativas / notícias neutras)")


if __name__ == "__main__":
    main()
