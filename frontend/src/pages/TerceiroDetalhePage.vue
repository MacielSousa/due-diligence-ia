<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'
import PageHeader from '@/components/ui/PageHeader.vue'
import ApiErrorMessage from '@/components/ui/ApiErrorMessage.vue'
import RiscoCard from '@/components/risco/RiscoCard.vue'
import ApontamentosCard from '@/components/terceiros/ApontamentosCard.vue'
import SociosCard from '@/components/terceiros/SociosCard.vue'
import AnexosCard from '@/components/terceiros/AnexosCard.vue'
import TerceiroDialog from '@/components/terceiros/TerceiroDialog.vue'
import { useTerceirosStore } from '@/stores/terceiros'
import { useNotificacao } from '@/composables/useNotificacao'
import { formatarDocumento } from '@/utils/formatar'

const props = defineProps<{ id: string }>()

const { t } = useI18n()
const router = useRouter()
const store = useTerceirosStore()
const { atual: terceiro, carregandoAtual, erroAtual } = storeToRefs(store)
const notificacao = useNotificacao()

const idNum = computed(() => Number(props.id))
const editarVisivel = ref(false)

// carrega o terceiro sempre que o :id da rota mudar (ex.: clicar num sócio)
watch(idNum, (id) => store.carregar(id), { immediate: true })

function excluir(): void {
  if (!terceiro.value) return
  notificacao.confirmarExclusao(
    terceiro.value.nome,
    () => store.excluir(idNum.value),
    () => router.push({ name: 'terceiros' }),
  )
}
</script>

<template>
  <section class="space-y-5">
    <div v-if="carregandoAtual && !terceiro" class="grid place-items-center py-20"><ProgressSpinner /></div>

    <template v-else-if="erroAtual">
      <RouterLink :to="{ name: 'terceiros' }" class="inline-flex items-center gap-2 text-sm text-surface-500 hover:text-primary">
        <i class="pi pi-arrow-left" /> {{ t('comum.voltar') }}
      </RouterLink>
      <ApiErrorMessage :erro="erroAtual" />
    </template>

    <template v-else-if="terceiro">
      <PageHeader :titulo="terceiro.nome">
        <template #antes>
          <RouterLink :to="{ name: 'terceiros' }" class="inline-flex items-center gap-2 text-sm text-surface-500 hover:text-primary">
            <i class="pi pi-arrow-left" /> {{ t('comum.voltar') }}
          </RouterLink>
        </template>
        <template #tags>
          <Tag :value="t(`tipo.${terceiro.tipo}`)" severity="secondary" />
          <Tag v-if="terceiro.pep" :value="t('terceiro.pepLongo')" severity="warn" />
        </template>
        <template #info>
          <div class="font-mono text-sm text-surface-500 mt-1">{{ formatarDocumento(terceiro.documento) }}</div>
        </template>
        <template #acoes>
          <Button :label="t('comum.editar')" icon="pi pi-pencil" severity="secondary" outlined @click="editarVisivel = true" />
          <Button :label="t('comum.excluir')" icon="pi pi-trash" severity="danger" outlined @click="excluir" />
        </template>
      </PageHeader>

      <div class="grid lg:grid-cols-3 gap-5 items-start">
        <RiscoCard :terceiro-id="idNum" />
        <div class="lg:col-span-2 space-y-5">
          <ApontamentosCard />
          <SociosCard v-if="terceiro.tipo === 'pj'" />
          <AnexosCard />
        </div>
      </div>

      <TerceiroDialog v-model:visible="editarVisivel" :terceiro="terceiro" />
    </template>
  </section>
</template>
