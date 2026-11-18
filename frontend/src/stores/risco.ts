import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { riscoService } from '@/services/riscoService'
import { paraApiError, type ApiError } from '@/composables/useApi'
import { NIVEIS, type EstadoRisco, type LinhaRelatorio, type NivelRisco } from '@/types/risco'

/**
 * Estado do cálculo de risco: relatório geral + resultado por terceiro.
 * Erros aqui ficam no ESTADO (aparecem no lugar do resultado), não em toast.
 */
export const useRiscoStore = defineStore('risco', () => {
  // ---------- state ----------
  const ano = ref(new Date().getFullYear())
  const relatorio = ref<LinhaRelatorio[]>([])
  const carregandoRelatorio = ref(false)
  const erroRelatorio = ref<ApiError | null>(null)
  const relatorioDesatualizado = ref(true)

  const porTerceiro = ref<Record<number, EstadoRisco>>({})

  // ---------- getters ----------
  const contagemPorNivel = computed(() => {
    const contagem = Object.fromEntries(NIVEIS.map((n) => [n, 0])) as Record<NivelRisco, number>
    for (const linha of relatorio.value) {
      if (linha.nivel) contagem[linha.nivel]++
    }
    return contagem
  })

  // ---------- actions ----------
  async function carregarRelatorio(): Promise<void> {
    carregandoRelatorio.value = true
    erroRelatorio.value = null
    try {
      relatorio.value = (await riscoService.relatorio(ano.value)).data
      relatorioDesatualizado.value = false
    } catch (e) {
      relatorio.value = []
      erroRelatorio.value = paraApiError(e)
    } finally {
      carregandoRelatorio.value = false
    }
  }

  async function calcular(terceiroId: number): Promise<void> {
    porTerceiro.value[terceiroId] = { carregando: true, resultado: null, erro: null }
    try {
      const resultado = (await riscoService.calcular(terceiroId, ano.value)).data
      porTerceiro.value[terceiroId] = { carregando: false, resultado, erro: null }
    } catch (e) {
      porTerceiro.value[terceiroId] = { carregando: false, resultado: null, erro: paraApiError(e) }
    }
  }

  /** Descarta resultados antigos depois de uma alteração (sem id = todos). */
  function invalidar(terceiroId: number | null = null): void {
    if (terceiroId === null) porTerceiro.value = {}
    else delete porTerceiro.value[terceiroId]
    relatorioDesatualizado.value = true
  }

  function trocarAno(novoAno: number): void {
    ano.value = novoAno
    invalidar()
  }

  return {
    ano, relatorio, carregandoRelatorio, erroRelatorio, relatorioDesatualizado, porTerceiro,
    contagemPorNivel,
    carregarRelatorio, calcular, invalidar, trocarAno,
  }
})
