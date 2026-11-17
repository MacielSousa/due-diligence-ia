<script setup lang="ts">
import { computed } from 'vue'
import { rotuloNivel, rotuloStatus } from '../formatar'
import type { Nivel, Status } from '../types'

const props = defineProps<{ nivel: Nivel | null; status?: Status }>()

const texto = computed(() => {
  if (props.nivel) return rotuloNivel[props.nivel]
  return props.status ? rotuloStatus[props.status] : '—'
})
</script>

<template>
  <span class="selo" :class="nivel ?? 'neutro'" data-test="selo">{{ texto }}</span>
</template>
