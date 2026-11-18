// O plugin tailwindcss-primeui não publica tipos
declare module 'tailwindcss-primeui' {
  import type { PluginCreator } from 'tailwindcss/types/config'
  const plugin: { handler: PluginCreator }
  export default plugin
}
