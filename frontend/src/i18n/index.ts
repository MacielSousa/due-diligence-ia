import { createI18n } from 'vue-i18n'
import ptBR from './locales/pt-BR.json'
import en from './locales/en.json'

export type Idioma = 'pt-BR' | 'en'
export type MessageSchema = typeof ptBR

export const IDIOMAS: { code: Idioma; label: string }[] = [
  { code: 'pt-BR', label: 'Português' },
  { code: 'en', label: 'English' },
]

export function isIdioma(valor: unknown): valor is Idioma {
  return IDIOMAS.some((i) => i.code === valor)
}

function idiomaSalvo(): Idioma | null {
  try {
    const salvo = localStorage.getItem('idioma')
    return isIdioma(salvo) ? salvo : null
  } catch {
    return null
  }
}

const inicial: Idioma = idiomaSalvo() ?? 'pt-BR'
document.documentElement.lang = inicial

const i18n = createI18n<[MessageSchema], Idioma, false>({
  legacy: false, // Composition API: useI18n()
  locale: inicial,
  fallbackLocale: 'pt-BR',
  messages: { 'pt-BR': ptBR, en },
})

export default i18n
