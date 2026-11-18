import { useApi } from '@/composables/useApi'
import type { LinhaRelatorio, RelatorioMeta, ResultadoRisco } from '@/types/risco'

/** Service de risco: endpoints que usam as classes do desafio no backend. */
const api = useApi()

export const riscoService = {
  calcular: (terceiroId: number, ano: number) => api.get<ResultadoRisco>(`/terceiros/${terceiroId}/risco`, { ano }),
  relatorio: (ano: number) => api.get<LinhaRelatorio[], RelatorioMeta>('/relatorio-risco', { ano }),
}
