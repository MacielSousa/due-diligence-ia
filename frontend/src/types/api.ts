/**
 * Contrato das respostas da API (espelha o App\Http\Responses\ApiResponse do Laravel).
 *
 * Sucesso: { success: true,  message, data, meta? }
 * Erro:    { success: false, message, code, errors?, detail? }
 */

/** Códigos de erro (espelha o enum App\Http\Responses\ErrorCode) + erro de rede do próprio front. */
export type ErrorCode =
  | 'validation_error'
  | 'business_rule'
  | 'not_found'
  | 'method_not_allowed'
  | 'domain_incomplete'
  | 'http_error'
  | 'internal_error'
  | 'network_error'

/** Erros de validação por campo: { nome: ['O campo nome é obrigatório.'] } */
export type ErrosValidacao = Record<string, string[]>

export interface ApiSucesso<T, M = Record<string, unknown>> {
  success: true
  message: string | null
  data: T
  meta?: M
}

export interface ApiFalha {
  success: false
  message: string
  code: ErrorCode
  errors?: ErrosValidacao
  detail?: string
}

export type ApiResposta<T, M = Record<string, unknown>> = ApiSucesso<T, M> | ApiFalha

export type MetodoHttp = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

export type QueryParams = Record<string, string | number | boolean | null | undefined>
