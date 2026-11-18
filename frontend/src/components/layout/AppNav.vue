<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter, type RouteRecordNormalized } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

// o menu é montado a partir das rotas com meta.menu (nova página = só registrar a rota)
const itens = computed(() =>
  router
    .getRoutes()
    .filter((r) => r.meta.menu && typeof r.name === 'string')
    .sort((a, b) => (a.meta.ordem ?? 0) - (b.meta.ordem ?? 0)),
)

// a página de detalhe mantém "Terceiros" destacado (meta.menuPai)
function ativo(item: RouteRecordNormalized): boolean {
  return route.name === item.name || route.meta.menuPai === item.name
}
</script>

<template>
  <nav class="flex gap-1">
    <RouterLink
      v-for="item in itens"
      :key="String(item.name)"
      :to="{ name: item.name }"
      class="px-3 py-2 rounded-md text-sm flex items-center gap-2 transition-colors"
      :class="ativo(item) ? 'bg-primary-50 text-primary-700 font-medium' : 'text-surface-600 hover:bg-surface-100'"
    >
      <i :class="item.meta.icone" /> {{ t(item.meta.titulo ?? '') }}
    </RouterLink>
  </nav>
</template>
