import type { Avaliacao, ChamadaLlm, Documento, Fornecedor, TipoDocumento } from './types'

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors: Record<string, string[]> = {},
  ) {
    super(message)
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`/api${path}`, {
    ...init,
    headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...init.headers },
  })
  const body = await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new ApiError(body.message ?? `Erro ${response.status}`, response.status, body.errors ?? {})
  }

  return body.data as T
}

const post = <T>(path: string, data: unknown) => request<T>(path, { method: 'POST', body: JSON.stringify(data) })

export const api = {
  fornecedores: () => request<Fornecedor[]>('/fornecedores'),
  fornecedor: (id: number) => request<Fornecedor>(`/fornecedores/${id}`),
  criarFornecedor: (dados: Partial<Fornecedor>) => post<Fornecedor>('/fornecedores', dados),

  tiposDocumento: () => request<TipoDocumento[]>('/documentos/tipos'),
  documentos: (fornecedorId: number) => request<Documento[]>(`/fornecedores/${fornecedorId}/documentos`),
  enviarDocumento: (fornecedorId: number, dados: { tipo: string; titulo: string; emitido_em?: string; conteudo: string }) =>
    post<Documento>(`/fornecedores/${fornecedorId}/documentos`, dados),

  avaliacoes: (fornecedorId: number) => request<Avaliacao[]>(`/fornecedores/${fornecedorId}/avaliacoes`),
  solicitarAvaliacao: (fornecedorId: number) => post<Avaliacao>(`/fornecedores/${fornecedorId}/avaliacoes`, {}),
  avaliacao: (id: number) => request<Avaliacao>(`/avaliacoes/${id}`),
  auditoria: (id: number) => request<ChamadaLlm[]>(`/avaliacoes/${id}/auditoria`),
  decidir: (id: number, dados: { decisao: string; decidido_por: string; justificativa: string }) =>
    post<Avaliacao>(`/avaliacoes/${id}/decisao`, dados),
}
