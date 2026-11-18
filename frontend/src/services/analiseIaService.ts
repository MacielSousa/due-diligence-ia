import { useApi } from '@/composables/useApi'
import type { AnaliseIa } from '@/types/analise'

/** Endpoints da análise por IA (assíncrona: iniciar responde 202 e o resultado sai depois). */
const api = useApi()

export const analiseIaService = {
  iniciar: (terceiroId: number, ano: number) => api.post<AnaliseIa>(`/terceiros/${terceiroId}/analises-ia`, { ano }),
  listar: (terceiroId: number) => api.get<AnaliseIa[]>(`/terceiros/${terceiroId}/analises-ia`),
  buscar: (terceiroId: number, analiseId: number) => api.get<AnaliseIa>(`/terceiros/${terceiroId}/analises-ia/${analiseId}`),
}
