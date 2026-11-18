<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AppNav from './AppNav.vue'
import LanguageSwitcher from './LanguageSwitcher.vue'

const { t } = useI18n()
</script>

<template>
  <header class="bg-surface-0 border-b border-surface-200 sticky top-0 z-10">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-x-6 gap-y-3">
      <RouterLink :to="{ name: 'relatorio' }" class="flex items-center gap-3 mr-auto">
        <span class="w-9 h-9 rounded-lg bg-primary text-primary-contrast grid place-items-center">
          <i class="pi pi-shield" />
        </span>
        <span class="leading-tight">
          <span class="block font-semibold">{{ t('app.titulo') }}</span>
          <span class="block text-xs text-surface-500">{{ t('app.subtitulo') }}</span>
        </span>
      </RouterLink>

      <AppNav />
      <LanguageSwitcher />
    </div>
  </header>
</template>
