<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import Tag from 'primevue/tag'
import { severidadeNivel } from '@/utils/formatar'

defineProps<{ nivel: string }>()
const { t, te } = useI18n()
</script>

<template>
  <!-- te() = "existe tradução?": se o domínio devolver outro texto, mostra como veio -->
  <Tag :value="te(`nivel.${nivel}`) ? t(`nivel.${nivel}`) : nivel" :severity="severidadeNivel(nivel)" />
</template>
