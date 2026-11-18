import type { NivelRisco } from '@/types/risco'

export type Severidade = 'success' | 'info' | 'warn' | 'danger' | 'secondary'

/** 52998224725 -> 529.982.247-25 | 11222333000181 -> 11.222.333/0001-81 */
export function formatarDocumento(doc = ''): string {
  if (doc.length === 11) return doc.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
  if (doc.length === 14) return doc.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
  return doc
}

/** 1536 -> "1,5 KB" (usa o idioma da interface) */
export function formatarBytes(bytes: number, locale: string): string {
  const unidades = ['B', 'KB', 'MB', 'GB']
  let valor = bytes
  let i = 0
  while (valor >= 1024 && i < unidades.length - 1) {
    valor /= 1024
    i++
  }
  return `${valor.toLocaleString(locale, { maximumFractionDigits: 1 })} ${unidades[i]}`
}

/** ISO 8601 -> "18/11/2026 14:30" (pt-BR) ou "11/18/2026, 2:30 PM" (en) */
export function formatarDataHora(iso: string, locale: string): string {
  return new Date(iso).toLocaleString(locale, { dateStyle: 'short', timeStyle: 'short' })
}

/** Ícone do PrimeIcons conforme o tipo do arquivo. */
export function iconeArquivo(mime: string): string {
  if (mime === 'application/pdf') return 'pi pi-file-pdf'
  if (mime.startsWith('image/')) return 'pi pi-image'
  return 'pi pi-file'
}

const SEVERIDADE_POR_NIVEL: Record<NivelRisco, Severidade> = {
  BAIXO: 'success',
  MEDIO: 'info',
  ALTO: 'warn',
  CRITICO: 'danger',
}

/** Cor da Tag do PrimeVue para cada nível de risco. */
export function severidadeNivel(nivel: string): Severidade {
  return SEVERIDADE_POR_NIVEL[nivel as NivelRisco] ?? 'secondary'
}
