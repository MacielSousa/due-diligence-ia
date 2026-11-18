<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <footer class="border-t border-surface-200 bg-surface-0">
    <div class="max-w-6xl mx-auto px-4 py-4 text-xs text-surface-500">{{ t('layout.rodape') }}</div>
  </footer>
</template>
