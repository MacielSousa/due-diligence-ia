<script setup lang="ts">
import { computed } from 'vue'
import { faixaNota } from '../formatar'
import type { Dimensao } from '../types'

const props = defineProps<{ dimensao: Dimensao }>()
const faixa = computed(() => faixaNota(props.dimensao.nota))
</script>

<template>
  <div class="dimensao">
    <div class="linha entre">
      <strong>{{ dimensao.rotulo }}</strong>
      <span class="selo" :class="faixa" data-test="nota">{{ dimensao.nota }}/100</span>
    </div>
    <div class="trilho" role="meter" :aria-valuenow="dimensao.nota" aria-valuemin="0" aria-valuemax="100" :aria-label="dimensao.rotulo">
      <div class="preenchimento" :class="faixa" :style="{ width: `${dimensao.nota}%` }" />
    </div>
    <p class="suave">{{ dimensao.justificativa }}</p>
  </div>
</template>

<style scoped>
.dimensao { margin-bottom: 14px; }
.trilho { height: 8px; background: var(--fundo); border-radius: 999px; overflow: hidden; margin: 6px 0; border: 1px solid var(--borda); }
.preenchimento { height: 100%; border-radius: 999px; }
.preenchimento.baixo { background: var(--baixo); }
.preenchimento.medio { background: var(--medio); }
.preenchimento.elevado { background: var(--elevado); }
p { margin: 0; }
</style>
