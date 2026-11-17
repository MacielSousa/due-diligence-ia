import type { Nivel } from './types'

export function moeda(valor: string | number | null): string {
  if (valor === null || valor === '') return '—'
  const numero = Number(valor)
  if (numero >= 1e9) return `R$ ${(numero / 1e9).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} bi`
  if (numero >= 1e6) return `R$ ${(numero / 1e6).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mi`
  return numero.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

export function data(iso: string | null): string {
  if (!iso) return '—'
  const d = new Date(iso.length === 10 ? `${iso}T00:00:00` : iso)
  return d.toLocaleDateString('pt-BR')
}

export function percentual(valor: number | null): string {
  return valor === null ? '—' : `${Math.round(valor * 100)}%`
}

/** Faixa visual de uma nota de risco 0–100 (mesmos limiares da política padrão). */
export function faixaNota(nota: number): Nivel {
  if (nota >= 65) return 'elevado'
  if (nota >= 35) return 'medio'
  return 'baixo'
}

export const rotuloNivel: Record<Nivel, string> = { baixo: 'BAIXO', medio: 'MÉDIO', elevado: 'ELEVADO' }

export const rotuloStatus: Record<string, string> = {
  pendente: 'Na fila',
  processando: 'Processando',
  concluida: 'Concluída',
  falhou: 'Falhou',
}

export const rotuloDecisao: Record<string, string> = {
  aprovado: 'Aprovado',
  aprovado_com_ressalvas: 'Aprovado com ressalvas',
  reprovado: 'Reprovado',
}
