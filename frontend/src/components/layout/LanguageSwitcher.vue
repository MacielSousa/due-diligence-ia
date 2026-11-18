<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Select from 'primevue/select'
import { IDIOMAS, isIdioma } from '@/i18n'
import { useAppStore } from '@/stores/app'

const { t } = useI18n()
const app = useAppStore()

const idioma = computed({
  get: () => app.idioma,
  set: (code: unknown) => isIdioma(code) && app.trocarIdioma(code),
})
</script>

<template>
  <Select v-model="idioma" :options="IDIOMAS" option-label="label" option-value="code" :aria-label="t('app.idioma')" class="w-36">
    <template #dropdownicon><i class="pi pi-globe" /></template>
  </Select>
</template>
