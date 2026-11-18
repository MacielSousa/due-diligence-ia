/** Tipos da análise de documentos por IA (espelham o AnaliseIaResource do Laravel). */
import type { NivelRisco } from './risco'
import type { CategoriaAnexo, TipoTerceiro } from './terceiro'

export type StatusAnalise = 'pendente' | 'processando' | 'concluida' | 'falhou'
export type StatusDocumento = 'pendente' | 'processando' | 'concluido' | 'ignorado'
export type Recomendacao = 'aprovar' | 'aprovar_com_ressalvas' | 'revisao_comite' | 'reprovar'

/** Um achado sugerido pela IA, já conferido pelas travas do código. */
export interface AchadoIa {
  tipo: string
  ano: number | null
  envolvido: string
  trecho: string
  eliminatorio: boolean
  valido: boolean
  motivo: string | null // por que foi descartado
  entra_no_score: boolean
}

export interface DocumentoAnalisado {
  id: number
  anexo_id: number | null
  nome: string
  categoria: CategoriaAnexo
  status: StatusDocumento
  duracao_ms: number | null
  caracteres: number | null
  tokens_entrada: number | null
  tokens_saida: number | null
  achados: AchadoIa[]
  erro: string | null
}

/** Como cada apontamento pesou no score. */
export interface ApontamentoExplicado {
  tipo: string
  ano: number
  peso_base: number
  idade: number
  depreciado: boolean
  peso: number
}

export interface SocioExplicado {
  nome: string
  participacao: number
  score_socio: number
  contribuicao: number
  explicacao: ExplicacaoRisco
}

/** O "porquê" do score, peso a peso (gerado pelo domínio). */
export interface ExplicacaoRisco {
  nome: string
  tipo: TipoTerceiro
  apontamentos: ApontamentoExplicado[]
  risco_proprio: number
  pep: boolean
  adicional_pep: number
  socios: SocioExplicado[]
  soma: number
  teto_aplicado: boolean
  score: number
  nivel?: NivelRisco
  faixas?: Record<NivelRisco, string>
  achados_informativos?: AchadoIa[]
}

export interface ResultadoAnalise {
  score: number
  nivel: NivelRisco
  eliminatorio: boolean
  recomendacao: Recomendacao
  explicacao: ExplicacaoRisco
}

export interface AnaliseIa {
  id: number
  status: StatusAnalise
  modelo: string
  ano_referencia: number
  progresso: { processados: number; total: number }
  metricas: {
    criada_em: string | null
    iniciada_em: string | null
    concluida_em: string | null
    duracao_ms: number | null
    espera_ms: number | null
    achados_validos: number
    achados_descartados: number
  }
  resultado: ResultadoAnalise | null
  erro: string | null
  documentos: DocumentoAnalisado[]
}
