export type Nivel = 'baixo' | 'medio' | 'elevado'
export type Status = 'pendente' | 'processando' | 'concluida' | 'falhou'

export interface Fornecedor {
  id: number
  razao_social: string
  cnpj: string
  cnpj_formatado: string
  segmento: string
  valor_contrato_estimado: string | null
  observacoes: string | null
  criado_em: string
}

export interface Documento {
  id: number
  tipo: string
  tipo_rotulo: string
  titulo: string
  emitido_em: string | null
  sha256: string
  tamanho_bytes: number
}

export interface TipoDocumento {
  valor: string
  rotulo: string
  obrigatorio: boolean
  validade_dias: number | null
}

export interface Achado {
  id: string
  documento_id: string
  dimensao: string
  severidade: 'baixa' | 'media' | 'alta' | 'critica'
  descricao: string
  evidencia: string
}

export interface Dimensao {
  dimensao: string
  rotulo: string
  nota: number
  justificativa: string
  achados: string[]
}

export interface SinalEliminatorio {
  tipo: string
  descricao: string
  documento_id: string
  evidencia: string
}

export interface Resultado {
  classificacao: Nivel
  classificacao_rotulo: string
  score: number
  confianca: number
  requer_revisao_humana: boolean
  resumo_executivo: string
  recomendacao: string
  motivos: string[]
  dimensoes: Dimensao[]
  sinais_eliminatorios: SinalEliminatorio[]
  documentos_faltantes: string[]
  documentos_vencidos: string[]
  analises_documentos: {
    documento_id: string
    resumo: string
    achados: Achado[]
    itens_descartados_sem_evidencia: number
  }[]
}

export interface Avaliacao {
  id: number
  fornecedor_id: number
  fornecedor?: { id: number; razao_social: string; cnpj_formatado: string }
  status: Status
  nivel: Nivel | null
  nivel_rotulo: string | null
  score: number | null
  confianca: number | null
  requer_revisao: boolean
  resultado?: Resultado | null
  erro: string | null
  decisao: string | null
  decidido_por: string | null
  justificativa_decisao: string | null
  decidido_em: string | null
  criado_em: string
}

export interface ChamadaLlm {
  id: string
  etapa: 'analise_documento' | 'consolidacao'
  driver: string
  modelo: string
  duracao_ms: number
  tokens_entrada: number | null
  tokens_saida: number | null
  erro: string | null
  prompt: string
  resposta: unknown
}
