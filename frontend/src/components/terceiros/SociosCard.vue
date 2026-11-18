<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import SectionCard from '@/components/ui/SectionCard.vue'
import CampoErro from '@/components/ui/CampoErro.vue'
import { useTerceirosStore } from '@/stores/terceiros'
import { useNotificacao } from '@/composables/useNotificacao'
import type { Socio, SocioPayload } from '@/types/terceiro'
import type { ErrosValidacao } from '@/types/api'

/** Quadro societário da PJ aberta (store.atual). */
const { t } = useI18n()
const store = useTerceirosStore()
const { atual, opcoesSocio, totalParticipacao } = storeToRefs(store)
const notificacao = useNotificacao()

const vazio = (): SocioPayload => ({ socio_id: null, participacao: null })
const form = ref<SocioPayload>(vazio())
const erros = ref<ErrosValidacao>({})
const salvando = ref(false)

// opções do select: todos os terceiros (sem filtro)
onMounted(() => notificacao.executar(() => store.carregarTodos(), { silencioso: true }))

async function adicionar(): Promise<void> {
  salvando.value = true
  const resposta = await notificacao.executar(() => store.adicionarSocio(form.value), { erros })
  if (resposta) form.value = vazio()
  salvando.value = false
}

function remover(vinculo: Socio): void {
  notificacao.executar(() => store.removerSocio(vinculo.id))
}
</script>

<template>
  <SectionCard v-if="atual" :titulo="t('socio.titulo')" icone="pi pi-sitemap">
    <template #extra>
      <span class="text-sm" :class="totalParticipacao > 100 ? 'text-red-600' : 'text-surface-500'">
        {{ t('socio.total', { total: totalParticipacao }) }}
      </span>
    </template>

    <form class="flex flex-wrap items-start gap-2" @submit.prevent="adicionar">
      <div class="flex flex-col gap-1 grow min-w-52">
        <Select v-model="form.socio_id" :options="opcoesSocio" option-label="nome" option-value="id" filter :placeholder="t('socio.selecione')" :invalid="!!erros.socio_id">
          <template #option="{ option }">
            <span>{{ option.nome }}</span>
            <span class="ml-2 text-xs text-surface-500">{{ t(`tipo.${option.tipo}`) }}</span>
          </template>
        </Select>
        <CampoErro :erros="erros.socio_id" />
      </div>
      <div class="flex flex-col gap-1">
        <InputNumber v-model="form.participacao" suffix="%" :min="0" :max="100" :max-fraction-digits="2" :aria-label="t('socio.participacao')" input-class="w-28" :invalid="!!erros.participacao" />
        <CampoErro :erros="erros.participacao" class="max-w-48" />
      </div>
      <Button type="submit" :label="t('comum.adicionar')" icon="pi pi-plus" :loading="salvando" />
    </form>

    <DataTable :value="atual.socios" data-key="id" size="small">
      <template #empty>{{ t('socio.nenhum') }}</template>
      <Column :header="t('socio.socio')">
        <template #body="{ data }">
          <RouterLink :to="{ name: 'terceiro', params: { id: data.socio.id } }" class="text-primary hover:underline">{{ data.socio.nome }}</RouterLink>
          <span class="ml-2 text-xs text-surface-500">{{ t(`tipo.${data.socio.tipo}`) }}</span>
        </template>
      </Column>
      <Column :header="t('socio.participacao')">
        <template #body="{ data }">{{ data.participacao }}%</template>
      </Column>
      <Column class="w-16 text-right">
        <template #body="{ data }">
          <Button icon="pi pi-trash" severity="danger" text rounded size="small" :aria-label="t('comum.excluir')" @click="remover(data)" />
        </template>
      </Column>
    </DataTable>
  </SectionCard>
</template>
