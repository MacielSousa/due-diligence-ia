import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import DimensaoBarra from '../components/DimensaoBarra.vue'
import NivelBadge from '../components/NivelBadge.vue'

describe('NivelBadge', () => {
  it('mostra o nível com a cor correspondente', () => {
    const wrapper = mount(NivelBadge, { props: { nivel: 'elevado' } })
    expect(wrapper.text()).toBe('ELEVADO')
    expect(wrapper.classes()).toContain('elevado')
  })

  it('mostra o status enquanto não há nível', () => {
    const wrapper = mount(NivelBadge, { props: { nivel: null, status: 'processando' } })
    expect(wrapper.text()).toBe('Processando')
    expect(wrapper.classes()).toContain('neutro')
  })
})

describe('DimensaoBarra', () => {
  it('desenha a barra proporcional à nota', () => {
    const wrapper = mount(DimensaoBarra, {
      props: {
        dimensao: { dimensao: 'financeira', rotulo: 'Financeira', nota: 70, justificativa: 'Prejuízo', achados: [] },
      },
    })
    expect(wrapper.get('[data-test="nota"]').text()).toBe('70/100')
    expect(wrapper.get('.preenchimento').attributes('style')).toContain('width: 70%')
    expect(wrapper.get('.preenchimento').classes()).toContain('elevado')
  })
})
