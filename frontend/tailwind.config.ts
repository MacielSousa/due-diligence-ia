import type { Config } from 'tailwindcss'
import tailwindcssPrimeUI from 'tailwindcss-primeui'

export default {
  content: ['./index.html', './src/**/*.{vue,ts}'],
  theme: { extend: {} },
  // expõe as cores do tema do PrimeVue no Tailwind: bg-primary, text-surface-500...
  plugins: [tailwindcssPrimeUI],
} satisfies Config
