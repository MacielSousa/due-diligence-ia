/// <reference types="vitest/config" />
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// No container "web" do Sail o alvo é http://laravel.test; fora dele, a API publicada em localhost:8080.
const apiTarget = process.env.API_PROXY_TARGET ?? 'http://localhost:8080'

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': { target: apiTarget, changeOrigin: true },
    },
    watch: { usePolling: true }, // volumes do WSL/Docker
  },
  test: {
    environment: 'jsdom',
  },
})
