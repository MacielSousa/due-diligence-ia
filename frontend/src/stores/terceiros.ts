import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { terceirosService } from '@/services/terceirosService'
import { paraApiError, type ApiError } from '@/composables/useApi'
import { useRiscoStore } from './risco'
import type { ApiSucesso } from '@/types/api'
import type {
  Anexo,
  AnexoPayload,
  Apontamento,
  ApontamentoPayload,
  FiltrosTerceiros,
  Socio,
  SocioPayload,
  Terceiro,
  TerceiroDetalhe,
  TerceiroPayload,
} from '@/types/terceiro'

/**
 * Estado dos terceiros: lista (com filtros), todos (para selects) e o terceiro aberto no detalhe.
 * Responsabilidade: guardar e atualizar o ESTADO. As chamadas HTTP ficam no terceirosService.
 * As actions devolvem a resposta da API (com a `message`) e lançam ApiError em caso de falha:
 * quem mostra a mensagem é o componente, via useNotificacao().executar().
 */
export const useTerceirosStore = defineStore('terceiros', () => {
  // ---------- state ----------
  const lista = ref<Terceiro[]>([])
  const filtros = ref<FiltrosTerceiros>({ busca: '', tipo: 'todos' })
  const carregandoLista = ref(false)

  const todos = ref<Terceiro[]>([]) // sem filtro, usado no select de sócios
  const atual = ref<TerceiroDetalhe | null>(null)
  const carregandoAtual = ref(false)
  const erroAtual = ref<ApiError | null>(null)

  // ---------- getters ----------
  const totalParticipacao = computed(() =>
    (atual.value?.socios ?? []).reduce((soma, s) => soma + Number(s.participacao), 0),
  )

  /** Quem ainda pode entrar como sócio do terceiro atual. */
  const opcoesSocio = computed<Terceiro[]>(() => {
    const empresa = atual.value
    if (!empresa) return []
    const jaSocios = new Set(empresa.socios.map((s) => s.socio.id))
    return todos.value.filter((t) => t.id !== empresa.id && !jaSocios.has(t.id))
  })

  function exigirAtual(): TerceiroDetalhe {
    if (!atual.value) throw new Error('Nenhum terceiro carregado.')
    return atual.value
  }

  // ---------- actions ----------
  async function carregarLista(): Promise<ApiSucesso<Terceiro[]>> {
    carregandoLista.value = true
    try {
      // o useApi descarta parâmetros vazios/null da query string
      const resposta = await terceirosService.listar({
        busca: filtros.value.busca.trim(),
        tipo: filtros.value.tipo === 'todos' ? null : filtros.value.tipo,
      })
      lista.value = resposta.data
      return resposta
    } finally {
      carregandoLista.value = false
    }
  }

  async function carregarTodos(): Promise<ApiSucesso<Terceiro[]>> {
    const resposta = await terceirosService.listar()
    todos.value = resposta.data
    return resposta
  }

  /** Erro de carregamento fica no estado (a página mostra no lugar do conteúdo). */
  async function carregar(id: number): Promise<void> {
    carregandoAtual.value = true
    erroAtual.value = null
    try {
      atual.value = (await terceirosService.buscar(id)).data
    } catch (e) {
      atual.value = null
      erroAtual.value = paraApiError(e)
    } finally {
      carregandoAtual.value = false
    }
  }

  /** Cria (sem id) ou atualiza (com id). */
  async function salvar(dados: TerceiroPayload, id?: number): Promise<ApiSucesso<Terceiro>> {
    const resposta = id ? await terceirosService.atualizar(id, dados) : await terceirosService.criar(dados)
    const salvo = resposta.data

    if (atual.value?.id === salvo.id) {
      atual.value = { ...atual.value, ...salvo }
    }
    useRiscoStore().invalidar(salvo.id)
    await Promise.all([carregarLista(), carregarTodos()])
    return resposta
  }

  async function excluir(id: number): Promise<ApiSucesso<null>> {
    const resposta = await terceirosService.excluir(id)
    lista.value = lista.value.filter((t) => t.id !== id)
    todos.value = todos.value.filter((t) => t.id !== id)
    if (atual.value?.id === id) atual.value = null
    useRiscoStore().invalidar()
    return resposta
  }

  async function adicionarApontamento(dados: ApontamentoPayload): Promise<ApiSucesso<Apontamento>> {
    const terceiro = exigirAtual()
    const resposta = await terceirosService.adicionarApontamento(terceiro.id, dados)
    terceiro.apontamentos = [...terceiro.apontamentos, resposta.data].sort((a, b) => b.ano - a.ano)
    useRiscoStore().invalidar(terceiro.id)
    return resposta
  }

  async function removerApontamento(apontamentoId: number): Promise<ApiSucesso<null>> {
    const terceiro = exigirAtual()
    const resposta = await terceirosService.removerApontamento(terceiro.id, apontamentoId)
    terceiro.apontamentos = terceiro.apontamentos.filter((a) => a.id !== apontamentoId)
    useRiscoStore().invalidar(terceiro.id)
    return resposta
  }

  async function adicionarSocio(dados: SocioPayload): Promise<ApiSucesso<Socio>> {
    const empresa = exigirAtual()
    const resposta = await terceirosService.adicionarSocio(empresa.id, dados)
    empresa.socios = [...empresa.socios, resposta.data]
    useRiscoStore().invalidar() // muda o risco desta empresa e de quem a tem como sócia
    return resposta
  }

  async function removerSocio(socioId: number): Promise<ApiSucesso<null>> {
    const empresa = exigirAtual()
    const resposta = await terceirosService.removerSocio(empresa.id, socioId)
    empresa.socios = empresa.socios.filter((s) => s.id !== socioId)
    useRiscoStore().invalidar()
    return resposta
  }

  async function adicionarAnexo(dados: AnexoPayload): Promise<ApiSucesso<Anexo>> {
    const terceiro = exigirAtual()
    const resposta = await terceirosService.enviarAnexo(terceiro.id, dados.categoria, dados.arquivo)
    terceiro.anexos = [resposta.data, ...terceiro.anexos]
    return resposta
  }

  async function removerAnexo(anexoId: number): Promise<ApiSucesso<null>> {
    const terceiro = exigirAtual()
    const resposta = await terceirosService.removerAnexo(terceiro.id, anexoId)
    terceiro.anexos = terceiro.anexos.filter((a) => a.id !== anexoId)
    return resposta
  }

  return {
    lista, filtros, carregandoLista, todos, atual, carregandoAtual, erroAtual,
    totalParticipacao, opcoesSocio,
    carregarLista, carregarTodos, carregar, salvar, excluir,
    adicionarApontamento, removerApontamento, adicionarSocio, removerSocio,
    adicionarAnexo, removerAnexo,
  }
})
