import { computed, ref } from 'vue'
import i18n from '@/i18n'
import type { ApiFalha, ApiResposta, ApiSucesso, ErrorCode, ErrosValidacao, MetodoHttp, QueryParams } from '@/types/api'

/**
 * useApi — conector HTTP com o backend.
 *
 * Responsabilidade ÚNICA: saber "como" falar com a API
 * (URL base, headers, idioma, JSON, query string, formato padrão de resposta e de erro).
 * Ele NÃO sabe quais endpoints existem — isso fica nos services.
 *
 *   const api = useApi()
 *   const resposta = await api.get<Terceiro[]>('/terceiros', { tipo: 'pf' })
 *   resposta.data    // Terceiro[]
 *   resposta.message // mensagem do backend (ou null)
 *   await api.post('/upload', formData) // FormData vai como multipart (upload)
 *
 * Qualquer falha vira um ApiError (com code, message, errors e detail).
 */

const BASE_URL = (import.meta.env.VITE_API_URL ?? '/api').replace(/\/$/, '')

/** Erro padronizado de qualquer requisição. */
export class ApiError extends Error {
  readonly status: number
  readonly code: ErrorCode
  readonly errors: ErrosValidacao
  readonly detail: string | null

  constructor(status: number, corpo: Partial<ApiFalha> | null, mensagemPadrao?: string) {
    super(corpo?.message ?? mensagemPadrao ?? `HTTP ${status}`)
    this.name = 'ApiError'
    this.status = status
    this.code = corpo?.code ?? (status === 0 ? 'network_error' : 'http_error')
    this.errors = corpo?.errors ?? {}
    this.detail = corpo?.detail ?? null
  }

  get isValidacao(): boolean {
    return this.code === 'validation_error'
  }

  get semConexao(): boolean {
    return this.code === 'network_error'
  }
}

export function isApiError(erro: unknown): erro is ApiError {
  return erro instanceof ApiError
}

/** Garante um ApiError (erros inesperados de JS também seguem o padrão). */
export function paraApiError(erro: unknown): ApiError {
  if (isApiError(erro)) return erro
  const mensagem = erro instanceof Error ? erro.message : String(erro)
  return new ApiError(0, { message: mensagem, code: 'internal_error' })
}

export interface OpcoesRequisicao {
  params?: QueryParams
  headers?: Record<string, string>
  signal?: AbortSignal
}

/** '/terceiros' + { tipo: 'pf', busca: '' } -> '/api/terceiros?tipo=pf' (ignora vazios) */
function montarUrl(url: string, params: QueryParams = {}): string {
  const query = new URLSearchParams()
  for (const [chave, valor] of Object.entries(params)) {
    if (valor !== undefined && valor !== null && valor !== '') query.append(chave, String(valor))
  }
  const qs = query.toString()
  return `${BASE_URL}${url}${qs ? `?${qs}` : ''}`
}

async function request<T, M>(
  method: MetodoHttp,
  url: string,
  data: unknown,
  { params, headers = {}, signal }: OpcoesRequisicao = {},
): Promise<ApiSucesso<T, M>> {
  const temCorpo = data !== undefined
  // FormData (upload de arquivo) vai como multipart: o navegador define o Content-Type com o boundary
  const ehArquivo = data instanceof FormData

  let resposta: Response
  try {
    resposta = await fetch(montarUrl(url, params), {
      method,
      signal, // permite cancelar com AbortController
      headers: {
        Accept: 'application/json',
        'Accept-Language': i18n.global.locale.value, // backend responde as mensagens neste idioma
        ...(temCorpo && !ehArquivo ? { 'Content-Type': 'application/json' } : {}),
        ...headers,
      },
      body: ehArquivo ? data : temCorpo ? JSON.stringify(data) : undefined,
    })
  } catch (e) {
    if (e instanceof DOMException && e.name === 'AbortError') throw e
    throw new ApiError(0, null, 'Falha de conexão com a API') // backend fora do ar / rede
  }

  const corpo = (await resposta.json().catch(() => null)) as ApiResposta<T, M> | null

  if (!resposta.ok || !corpo || corpo.success === false) {
    throw new ApiError(resposta.status, corpo && corpo.success === false ? corpo : null)
  }

  return corpo
}

export function useApi() {
  const pendentes = ref(0)
  const erro = ref<ApiError | null>(null)
  const carregando = computed(() => pendentes.value > 0)

  async function executar<T, M>(method: MetodoHttp, url: string, data?: unknown, opcoes?: OpcoesRequisicao) {
    pendentes.value++
    erro.value = null
    try {
      return await request<T, M>(method, url, data, opcoes)
    } catch (e) {
      erro.value = paraApiError(e)
      throw e // quem chamou decide como mostrar (toast, erro no campo...)
    } finally {
      pendentes.value--
    }
  }

  return {
    carregando,
    erro,
    get: <T, M = Record<string, unknown>>(url: string, params?: QueryParams, opcoes: OpcoesRequisicao = {}) =>
      executar<T, M>('GET', url, undefined, { ...opcoes, params }),
    post: <T>(url: string, data: unknown = {}, opcoes?: OpcoesRequisicao) => executar<T, never>('POST', url, data, opcoes),
    put: <T>(url: string, data: unknown = {}, opcoes?: OpcoesRequisicao) => executar<T, never>('PUT', url, data, opcoes),
    patch: <T>(url: string, data: unknown = {}, opcoes?: OpcoesRequisicao) => executar<T, never>('PATCH', url, data, opcoes),
    delete: <T = null>(url: string, opcoes?: OpcoesRequisicao) => executar<T, never>('DELETE', url, undefined, opcoes),
  }
}
