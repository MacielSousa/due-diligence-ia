import { describe, expect, it } from 'vitest'
import { faixaNota, moeda, percentual } from '../formatar'

describe('formatar', () => {
  it('classifica a nota nas mesmas faixas da política de risco', () => {
    expect(faixaNota(10)).toBe('baixo')
    expect(faixaNota(35)).toBe('medio')
    expect(faixaNota(64)).toBe('medio')
    expect(faixaNota(65)).toBe('elevado')
  })

  it('abrevia valores de contratos bilionários', () => {
    expect(moeda('2500000000')).toBe('R$ 2,5 bi')
    expect(moeda(null)).toBe('—')
  })

  it('mostra confiança em percentual', () => {
    expect(percentual(0.82)).toBe('82%')
    expect(percentual(null)).toBe('—')
  })
})
