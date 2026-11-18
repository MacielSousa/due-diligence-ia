<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import SectionCard from '@/components/ui/SectionCard.vue'
import CampoErro from '@/components/ui/CampoErro.vue'
import { useTerceirosStore } from '@/stores/terceiros'
import { useNotificacao } from '@/composables/useNotificacao'
import { TIPOS_APONTAMENTO, type Apontamento, type ApontamentoPayload } from '@/types/terceiro'
import type { ErrosValidacao } from '@/types/api'

/** Lista e cadastro de apontamentos do terceiro aberto (store.atual). */
const { t } = useI18n()
const store = useTerceirosStore()
const { atual } = storeToRefs(store)
const notificacao = useNotificacao()

const vazio = (): ApontamentoPayload => ({ tipo: null, ano: new Date().getFullYear() })
const form = ref<ApontamentoPayload>(vazio())
const erros = ref<ErrosValidacao>({})
const salvando = ref(false)

const opcoes = computed(() => TIPOS_APONTAMENTO.map((tipo) => ({ label: t(`apontamento.${tipo}`), value: tipo })))

async function adicionar(): Promise<void> {
  salvando.value = true
  const resposta = await notificacao.executar(() => store.adicionarApontamento(form.value), { erros })
  if (resposta) form.value = vazio()
  salvando.value = false
}

function remover(apontamento: Apontamento): void {
  notificacao.executar(() => store.removerApontamento(apontamento.id))
}
</script>

<template>
  <SectionCard v-if="atual" :titulo="t('apontamento.titulo')" icone="pi pi-flag">
    <form class="flex flex-wrap items-start gap-2" @submit.prevent="adicionar">
      <div class="flex flex-col gap-1 grow min-w-52">
        <Select v-model="form.tipo" :options="opcoes" option-label="label" option-value="value" :placeholder="t('apontamento.selecione')" :invalid="!!erros.tipo" />
        <CampoErro :erros="erros.tipo" />
      </div>
      <div class="flex flex-col gap-1">
        <InputNumber v-model="form.ano" :use-grouping="false" :min="1900" :aria-label="t('apontamento.ano')" input-class="w-24" :invalid="!!erros.ano" />
        <CampoErro :erros="erros.ano" />
      </div>
      <Button type="submit" :label="t('comum.adicionar')" icon="pi pi-plus" :loading="salvando" />
    </form>

    <DataTable :value="atual.apontamentos" data-key="id" size="small">
      <template #empty>{{ t('apontamento.nenhum') }}</template>
      <Column :header="t('apontamento.tipo')">
        <template #body="{ data }">
          {{ t(`apontamento.${data.tipo}`) }}
          <Tag v-if="data.origem === 'ia'" value="IA" severity="info" icon="pi pi-sparkles" class="ml-2 !text-xs" />
          <div v-if="data.trecho" class="text-xs italic text-surface-500 mt-0.5 line-clamp-2" :title="data.trecho">"{{ data.trecho }}"</div>
        </template>
      </Column>
      <Column field="ano" :header="t('apontamento.ano')" />
      <Column class="w-16 text-right">
        <template #body="{ data }">
          <Button icon="pi pi-trash" severity="danger" text rounded size="small" :aria-label="t('comum.excluir')" @click="remover(data)" />
        </template>
      </Column>
    </DataTable>
  </SectionCard>
</template>
