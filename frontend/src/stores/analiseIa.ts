import { defineStore } from 'pinia'
import { ref } from 'vue'
import { analiseIaService } from '@/services/analiseIaService'
import { paraApiError, type ApiError } from '@/composables/useApi'
import { useTerceirosStore } from './terceiros'
import { useRiscoStore } from './risco'
import type { ApiSucesso } from '@/types/api'
import type { AnaliseIa } from '@/types/analise'

const INTERVALO_MS = 2000

/**
 * Estado da análise por IA de cada terceiro.
 * Como a análise roda em fila (jobs), a store ACOMPANHA o progresso consultando a API
 * a cada 2 s enquanto o status for pendente/processando.
 */
export const useAnaliseIaStore = defineStore('analiseIa', () => {
  const porTerceiro = ref<Record<number, AnaliseIa | null>>({})
  const erro = ref<Record<number, ApiError | null>>({})
  const timers = new Map<number, ReturnType<typeof setTimeout>>()

  const emAndamento = (a: AnaliseIa | null | undefined) => a?.status === 'pendente' || a?.status === 'processando'

  /** Busca a análise mais recente do terceiro (e volta a acompanhar se ainda estiver rodando). */
  async function carregarUltima(terceiroId: number): Promise<void> {
    try {
      const ultima = (await analiseIaService.listar(terceiroId)).data[0] ?? null
      porTerceiro.value[terceiroId] = ultima
      erro.value[terceiroId] = null
      if (emAndamento(ultima)) acompanhar(terceiroId)
    } catch (e) {
      erro.value[terceiroId] = paraApiError(e)
    }
  }

  async function iniciar(terceiroId: number, ano: number): Promise<ApiSucesso<AnaliseIa>> {
    const resposta = await analiseIaService.iniciar(terceiroId, ano)
    porTerceiro.value[terceiroId] = resposta.data
    acompanhar(terceiroId)
    return resposta
  }

  function acompanhar(terceiroId: number): void {
    parar(terceiroId)
    timers.set(terceiroId, setTimeout(() => atualizar(terceiroId), INTERVALO_MS))
  }

  async function atualizar(terceiroId: number): Promise<void> {
    const atual = porTerceiro.value[terceiroId]
    if (!atual) return
    try {
      const nova = (await analiseIaService.buscar(terceiroId, atual.id)).data
      porTerceiro.value[terceiroId] = nova
      if (emAndamento(nova)) {
        acompanhar(terceiroId)
      } else {
        timers.delete(terceiroId)
        await aoConcluir(terceiroId)
      }
    } catch (e) {
      erro.value[terceiroId] = paraApiError(e)
      acompanhar(terceiroId) // backend reiniciando? tenta de novo
    }
  }

  /** O Job 2 mudou apontamentos e score: recarrega o terceiro e descarta resultados de risco antigos. */
  async function aoConcluir(terceiroId: number): Promise<void> {
    useRiscoStore().invalidar()
    const terceiros = useTerceirosStore()
    if (terceiros.atual?.id === terceiroId) await terceiros.carregar(terceiroId)
  }

  function parar(terceiroId: number): void {
    clearTimeout(timers.get(terceiroId))
    timers.delete(terceiroId)
  }

  return { porTerceiro, erro, emAndamento, carregarUltima, iniciar, parar }
})
