<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import ProgressBar from 'primevue/progressbar'
import PageHeader from '@/components/ui/PageHeader.vue'
import ApiErrorMessage from '@/components/ui/ApiErrorMessage.vue'
import NivelTag from '@/components/risco/NivelTag.vue'
import ResumoNiveis from '@/components/risco/ResumoNiveis.vue'
import { useRiscoStore } from '@/stores/risco'

const { t } = useI18n()
const risco = useRiscoStore()
const { relatorio, carregandoRelatorio, erroRelatorio, contagemPorNivel } = storeToRefs(risco)

const ano = computed({
  get: () => risco.ano,
  set: (valor: number | null) => valor && risco.trocarAno(valor),
})

// só busca de novo se algo mudou desde a última vez (cadastro, ano...)
onMounted(() => {
  if (risco.relatorioDesatualizado) risco.carregarRelatorio()
})
</script>

<template>
  <section class="space-y-5">
    <PageHeader :titulo="t('relatorio.titulo')" :descricao="t('relatorio.descricao')">
      <template #acoes>
        <div class="flex flex-col gap-1">
          <label for="ano" class="text-sm">{{ t('risco.ano') }}</label>
          <InputNumber v-model="ano" input-id="ano" :use-grouping="false" :min="1900" :max="2100" show-buttons input-class="w-24" />
        </div>
        <Button :label="t('relatorio.atualizar')" icon="pi pi-refresh" :loading="carregandoRelatorio" @click="risco.carregarRelatorio()" />
      </template>
    </PageHeader>

    <ResumoNiveis :contagem="contagemPorNivel" />

    <ApiErrorMessage v-if="erroRelatorio" :erro="erroRelatorio" />

    <DataTable :value="relatorio" :loading="carregandoRelatorio" data-key="id" striped-rows class="rounded-lg overflow-hidden border border-surface-200">
      <template #empty>{{ t('comum.nenhumRegistro') }}</template>
      <Column field="nome" :header="t('terceiro.nome')">
        <template #body="{ data }">
          <RouterLink :to="{ name: 'terceiro', params: { id: data.id } }" class="text-primary font-medium hover:underline">{{ data.nome }}</RouterLink>
        </template>
      </Column>
      <Column field="tipo" :header="t('terceiro.tipo')">
        <template #body="{ data }">{{ t(`tipo.${data.tipo}`) }}</template>
      </Column>
      <Column field="score" :header="t('risco.score')" class="w-56">
        <template #body="{ data }">
          <div v-if="data.score !== null" class="flex items-center gap-3">
            <ProgressBar :value="data.score" :show-value="false" class="!h-2 w-28" />
            <span class="tabular-nums">{{ Number(data.score).toFixed(1) }}</span>
          </div>
          <span v-else class="text-surface-400">—</span>
        </template>
      </Column>
      <Column field="nivel" :header="t('risco.nivel')">
        <template #body="{ data }">
          <NivelTag v-if="data.nivel" :nivel="data.nivel" />
          <span v-else-if="data.erro" class="text-red-600 text-sm"><i class="pi pi-exclamation-circle mr-1" />{{ data.erro }}</span>
        </template>
      </Column>
    </DataTable>
  </section>
</template>
