<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import SelectButton from 'primevue/selectbutton'
import Tag from 'primevue/tag'
import PageHeader from '@/components/ui/PageHeader.vue'
import TerceiroDialog from '@/components/terceiros/TerceiroDialog.vue'
import { useTerceirosStore } from '@/stores/terceiros'
import { useNotificacao } from '@/composables/useNotificacao'
import { formatarDocumento } from '@/utils/formatar'
import type { FiltroTipo, Terceiro } from '@/types/terceiro'

const { t } = useI18n()
const router = useRouter()
const store = useTerceirosStore()
const { lista, filtros, carregandoLista } = storeToRefs(store)
const notificacao = useNotificacao()

const dialogVisivel = ref(false)

const opcoesTipo = computed<{ label: string; value: FiltroTipo }[]>(() => [
  { label: t('comum.todos'), value: 'todos' },
  { label: t('tipo.pf'), value: 'pf' },
  { label: t('tipo.pj'), value: 'pj' },
])

const carregar = () => notificacao.executar(() => store.carregarLista(), { silencioso: true })

// debounce: espera o usuário parar de digitar 300ms antes de chamar a API
let timer: ReturnType<typeof setTimeout> | undefined
watch(
  filtros,
  () => {
    clearTimeout(timer)
    timer = setTimeout(carregar, 300)
  },
  { deep: true },
)

onMounted(carregar)

function excluir(terceiro: Terceiro): void {
  notificacao.confirmarExclusao(terceiro.nome, () => store.excluir(terceiro.id))
}

function abrirNovo(novo: Terceiro): void {
  router.push({ name: 'terceiro', params: { id: novo.id } })
}
</script>

<template>
  <section class="space-y-5">
    <PageHeader :titulo="t('terceiro.titulo')" :descricao="t('terceiro.descricao')">
      <template #acoes>
        <Button :label="t('terceiro.novo')" icon="pi pi-plus" @click="dialogVisivel = true" />
      </template>
    </PageHeader>

    <div class="flex flex-wrap gap-3">
      <IconField class="grow min-w-60">
        <InputIcon class="pi pi-search" />
        <InputText v-model="filtros.busca" :placeholder="t('terceiro.buscar')" class="w-full" />
      </IconField>
      <SelectButton v-model="filtros.tipo" :options="opcoesTipo" option-label="label" option-value="value" :allow-empty="false" />
    </div>

    <DataTable :value="lista" :loading="carregandoLista" data-key="id" striped-rows class="rounded-lg overflow-hidden border border-surface-200">
      <template #empty>{{ t('comum.nenhumRegistro') }}</template>
      <Column field="nome" :header="t('terceiro.nome')">
        <template #body="{ data }">
          <RouterLink :to="{ name: 'terceiro', params: { id: data.id } }" class="text-primary font-medium hover:underline">{{ data.nome }}</RouterLink>
          <Tag v-if="data.pep" :value="t('terceiro.pep')" severity="warn" class="ml-2 !text-xs" />
        </template>
      </Column>
      <Column field="tipo" :header="t('terceiro.tipo')">
        <template #body="{ data }">{{ t(`tipo.${data.tipo}`) }}</template>
      </Column>
      <Column field="documento" :header="t('terceiro.documento')">
        <template #body="{ data }"><span class="font-mono text-sm">{{ formatarDocumento(data.documento) }}</span></template>
      </Column>
      <Column field="apontamentos_count" :header="t('apontamento.titulo')" class="text-center" />
      <Column :header="t('comum.acoes')" class="w-24 text-right">
        <template #body="{ data }">
          <Button icon="pi pi-trash" severity="danger" text rounded :aria-label="t('comum.excluir')" @click="excluir(data)" />
        </template>
      </Column>
    </DataTable>

    <TerceiroDialog v-model:visible="dialogVisivel" @salvo="abrirNovo" />
  </section>
</template>
