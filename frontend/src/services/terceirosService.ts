import { useApi } from '@/composables/useApi'
import type {
  Anexo,
  Apontamento,
  ApontamentoPayload,
  CategoriaAnexo,
  FiltrosTerceirosApi,
  Socio,
  SocioPayload,
  Terceiro,
  TerceiroDetalhe,
  TerceiroPayload,
} from '@/types/terceiro'

/**
 * Service de terceiros: responsabilidade de saber QUAIS endpoints existem e o TIPO de cada retorno.
 * Não guarda estado (isso é da store) e não sabe montar requisição (isso é do useApi).
 */
const api = useApi()

export const terceirosService = {
  listar: (filtros: FiltrosTerceirosApi = {}) => api.get<Terceiro[]>('/terceiros', { ...filtros }),
  buscar: (id: number) => api.get<TerceiroDetalhe>(`/terceiros/${id}`),
  criar: (dados: TerceiroPayload) => api.post<Terceiro>('/terceiros', dados),
  atualizar: (id: number, dados: TerceiroPayload) => api.put<Terceiro>(`/terceiros/${id}`, dados),
  excluir: (id: number) => api.delete(`/terceiros/${id}`),

  adicionarApontamento: (id: number, dados: ApontamentoPayload) => api.post<Apontamento>(`/terceiros/${id}/apontamentos`, dados),
  removerApontamento: (id: number, apontamentoId: number) => api.delete(`/terceiros/${id}/apontamentos/${apontamentoId}`),

  adicionarSocio: (id: number, dados: SocioPayload) => api.post<Socio>(`/terceiros/${id}/socios`, dados),
  removerSocio: (id: number, socioId: number) => api.delete(`/terceiros/${id}/socios/${socioId}`),

  /** Upload: monta um FormData (multipart) com a categoria e o arquivo. */
  enviarAnexo: (id: number, categoria: CategoriaAnexo | null, arquivo: File | null) => {
    const formData = new FormData()
    if (categoria) formData.append('categoria', categoria)
    if (arquivo) formData.append('arquivo', arquivo)
    return api.post<Anexo>(`/terceiros/${id}/anexos`, formData)
  },
  removerAnexo: (id: number, anexoId: number) => api.delete(`/terceiros/${id}/anexos/${anexoId}`),
}
