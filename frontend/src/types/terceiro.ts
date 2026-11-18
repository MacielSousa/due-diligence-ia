/** Tipos do domínio de terceiros (espelham os Resources do Laravel). */

export type TipoTerceiro = 'pf' | 'pj'

export const TIPOS_APONTAMENTO = ['sancao', 'processo_criminal', 'midia_negativa', 'processo_trabalhista'] as const
export type TipoApontamento = (typeof TIPOS_APONTAMENTO)[number]

export interface Apontamento {
  id: number
  tipo: TipoApontamento
  ano: number
}

export interface SocioResumo {
  id: number
  nome: string
  tipo: TipoTerceiro
}

/** Vínculo societário: um terceiro (PF ou PJ) com X% de uma PJ. */
export interface Socio {
  id: number
  participacao: number
  socio: SocioResumo
}

export interface Terceiro {
  id: number
  tipo: TipoTerceiro
  nome: string
  documento: string // só dígitos
  pep: boolean
  criado_em: string
  apontamentos_count?: number
  socios_count?: number
}

export const CATEGORIAS_ANEXO = [
  'contrato_social',
  'certidao_negativa',
  'documento_identidade',
  'comprovante_endereco',
  'outro',
] as const
export type CategoriaAnexo = (typeof CATEGORIAS_ANEXO)[number]

/** Arquivo anexado ao terceiro (AnexoResource). */
export interface Anexo {
  id: number
  categoria: CategoriaAnexo
  nome: string
  mime: string
  tamanho: number // bytes
  enviado_em: string
  download_url: string
}

/** GET /api/terceiros/{id} traz os relacionamentos. */
export interface TerceiroDetalhe extends Terceiro {
  apontamentos: Apontamento[]
  socios: Socio[]
  anexos: Anexo[]
}

// ---------- payloads (o que o front envia) ----------

export interface TerceiroPayload {
  tipo?: TipoTerceiro // só na criação
  nome: string
  documento: string
  pep: boolean
}

export interface ApontamentoPayload {
  tipo: TipoApontamento | null
  ano: number | null
}

export interface AnexoPayload {
  categoria: CategoriaAnexo | null
  arquivo: File | null
}

export interface SocioPayload {
  socio_id: number | null
  participacao: number | null
}

// ---------- filtros ----------

export type FiltroTipo = 'todos' | TipoTerceiro

export interface FiltrosTerceiros {
  busca: string
  tipo: FiltroTipo
}

export interface FiltrosTerceirosApi {
  busca?: string
  tipo?: TipoTerceiro | null
}
