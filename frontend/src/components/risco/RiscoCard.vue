<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import SectionCard from '@/components/ui/SectionCard.vue'
import ApiErrorMessage from '@/components/ui/ApiErrorMessage.vue'
import NivelTag from './NivelTag.vue'
import { useRiscoStore } from '@/stores/risco'

/** Calcula e mostra o risco de um terceiro (estado na store de risco). */
const props = defineProps<{ terceiroId: number }>()

const { t } = useI18n()
const riscoStore = useRiscoStore()

const risco = computed(() => riscoStore.porTerceiro[props.terceiroId] ?? null)
const ano = computed({
  get: () => riscoStore.ano,
  set: (valor: number | null) => valor && riscoStore.trocarAno(valor),
})
</script>

<template>
  <SectionCard :titulo="t('risco.titulo')" icone="pi pi-gauge">
    <div class="flex items-end gap-2">
      <div class="flex flex-col gap-1">
        <label for="ano-risco" class="text-sm">{{ t('risco.ano') }}</label>
        <InputNumber v-model="ano" input-id="ano-risco" :use-grouping="false" :min="1900" :max="2100" input-class="w-24" />
      </div>
      <Button :label="t('risco.calcular')" icon="pi pi-calculator" :loading="risco?.carregando" @click="riscoStore.calcular(terceiroId)" />
    </div>

    <div v-if="risco?.resultado" class="flex items-center gap-4 pt-2">
      <span class="text-5xl font-bold tabular-nums">{{ Number(risco.resultado.score).toFixed(1) }}</span>
      <NivelTag :nivel="risco.resultado.nivel" class="!text-base" />
    </div>
    <ApiErrorMessage v-else-if="risco?.erro" :erro="risco.erro" size="small" />
    <p v-else-if="!risco?.carregando" class="text-sm text-surface-500">{{ t('risco.naoCalculado') }}</p>
  </SectionCard>
</template>
