import { defineStore } from 'pinia'
import { ref } from 'vue'
import i18n, { type Idioma } from '@/i18n'

/** Estado global da interface (por enquanto, o idioma). */
export const useAppStore = defineStore('app', () => {
  const idioma = ref<Idioma>(i18n.global.locale.value)

  function trocarIdioma(code: Idioma): void {
    idioma.value = code
    i18n.global.locale.value = code
    document.documentElement.lang = code
    try {
      localStorage.setItem('idioma', code)
    } catch {
      /* navegador sem localStorage: só não lembra a escolha */
    }
  }

  return { idioma, trocarIdioma }
})
