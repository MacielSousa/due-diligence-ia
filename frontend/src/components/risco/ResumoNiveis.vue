<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { NIVEIS, type NivelRisco } from '@/types/risco'
import { severidadeNivel, type Severidade } from '@/utils/formatar'

/** Cards com a quantidade de terceiros em cada nível de risco. */
defineProps<{ contagem: Record<NivelRisco, number> }>()
const { t } = useI18n()

const corBorda: Partial<Record<Severidade, string>> = {
  success: 'border-l-green-500',
  info: 'border-l-sky-500',
  warn: 'border-l-orange-500',
  danger: 'border-l-red-500',
}
</script>

<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div
      v-for="nivel in NIVEIS"
      :key="nivel"
      class="bg-surface-0 rounded-lg border border-surface-200 border-l-4 p-4"
      :class="corBorda[severidadeNivel(nivel)]"
    >
      <div class="text-sm text-surface-500">{{ t(`nivel.${nivel}`) }}</div>
      <div class="text-3xl font-semibold tabular-nums">{{ contagem[nivel] }}</div>
    </div>
  </div>
</template>
