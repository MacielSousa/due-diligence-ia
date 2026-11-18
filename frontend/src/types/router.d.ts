import 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    /** chave i18n do título (menu e aba do navegador) */
    titulo?: string
    /** aparece no menu principal */
    menu?: boolean
    icone?: string
    ordem?: number
    /** item do menu que fica destacado nesta rota */
    menuPai?: string
  }
}

export {}
