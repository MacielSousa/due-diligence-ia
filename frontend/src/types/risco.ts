import type { TipoTerceiro } from './terceiro'
import type { ApiError } from '@/composables/useApi'

/** Do mais grave para o menos grave (ordem usada nos cards de resumo). */
export const NIVEIS = ['CRITICO', 'ALTO', 'MEDIO', 'BAIXO'] as const
export type NivelRisco = (typeof NIVEIS)[number]

/** GET /api/terceiros/{id}/risco */
export interface ResultadoRisco {
  terceiro_id: number
  nome: string
  ano: number
  score: number
  nivel: NivelRisco
}

/** Linha do GET /api/relatorio-risco (score/nivel null quando o domínio barrou o terceiro). */
export interface LinhaRelatorio {
  id: number
  nome: string
  tipo: TipoTerceiro
  score: number | null
  nivel: NivelRisco | null
  erro: string | null
}

export interface RelatorioMeta {
  ano: number
}

/** Estado do cálculo de risco de um terceiro na store. */
export interface EstadoRisco {
  carregando: boolean
  resultado: ResultadoRisco | null
  erro: ApiError | null
}
