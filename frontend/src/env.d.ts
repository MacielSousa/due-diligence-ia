/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** URL base da API (padrão: /api, repassado pelo proxy do Vite) */
  readonly VITE_API_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
