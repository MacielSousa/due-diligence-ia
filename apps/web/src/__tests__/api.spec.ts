import { afterEach, describe, expect, it, vi } from 'vitest'
import { api, ApiError } from '../api'

function responder(status: number, body: unknown) {
  return vi.spyOn(globalThis, 'fetch').mockResolvedValue(
    new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }),
  )
}

afterEach(() => vi.restoreAllMocks())

describe('api', () => {
  it('desembrulha o campo data e usa o prefixo /api', async () => {
    const fetch = responder(200, { data: [{ id: 1, razao_social: 'Alfa' }] })

    const fornecedores = await api.fornecedores()

    expect(fornecedores[0].razao_social).toBe('Alfa')
    expect(fetch).toHaveBeenCalledWith('/api/fornecedores', expect.objectContaining({ headers: expect.any(Object) }))
  })

  it('converte erros de validação em ApiError', async () => {
    responder(422, { message: 'CNPJ inválido.', errors: { cnpj: ['CNPJ inválido.'] } })

    const erro = await api.criarFornecedor({ cnpj: '1' }).catch((e) => e)

    expect(erro).toBeInstanceOf(ApiError)
    expect(erro.status).toBe(422)
    expect(erro.errors.cnpj[0]).toBe('CNPJ inválido.')
  })

  it('envia a avaliação com POST', async () => {
    const fetch = responder(202, { data: { id: 7, status: 'pendente' } })

    const avaliacao = await api.solicitarAvaliacao(3)

    expect(avaliacao.id).toBe(7)
    expect(fetch.mock.calls[0][0]).toBe('/api/fornecedores/3/avaliacoes')
    expect(fetch.mock.calls[0][1]?.method).toBe('POST')
  })
})
