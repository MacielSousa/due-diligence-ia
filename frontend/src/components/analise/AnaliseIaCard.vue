<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Message from 'primevue/message'
import ProgressBar from 'primevue/progressbar'
import Tag from 'primevue/tag'
import SectionCard from '@/components/ui/SectionCard.vue'
import NivelTag from '@/components/risco/NivelTag.vue'
import ExplicacaoScore from './ExplicacaoScore.vue'
import DocumentoAnalisadoItem from './DocumentoAnalisadoItem.vue'
import { useAnaliseIaStore } from '@/stores/analiseIa'
import { useRiscoStore } from '@/stores/risco'
import { useTerceirosStore } from '@/stores/terceiros'
import { useNotificacao } from '@/composables/useNotificacao'
import { formatarDataHora, formatarDuracao, type Severidade } from '@/utils/formatar'
import type { Recomendacao } from '@/types/analise'

/**
 * Análise dos documentos por IA (Llama local).
 * Dispara o job, acompanha o progresso e mostra o resultado: score, recomendação,
 * métricas e o porquê do score peso a peso, com as evidências de cada documento.
 */
const props = defineProps<{ terceiroId: number }>()

const { t, locale } = useI18n()
const store = useAnaliseIaStore()
const { porTerceiro } = storeToRefs(store)
const terceiros = useTerceirosStore()
const risco = useRiscoStore()
const notificacao = useNotificacao()

const analise = computed(() => porTerceiro.value[props.terceiroId] ?? null)
const rodando = computed(() => store.emAndamento(analise.value))
const iniciando = ref(false)
const temDocumentos = computed(() => (terceiros.atual?.anexos.length ?? 0) > 0)
const percentual = computed(() => {
  const p = analise.value?.progresso
  return p && p.total ? Math.round((p.processados / p.total) * 100) : 0
})

const SEVERIDADE_RECOMENDACAO: Record<Recomendacao, Severidade> = {
  aprovar: 'success',
  aprovar_com_ressalvas: 'info',
  revisao_comite: 'warn',
  reprovar: 'danger',
}

watch(() => props.terceiroId, (id) => store.carregarUltima(id), { immediate: true })
onBeforeUnmount(() => store.parar(props.terceiroId))

async function analisar(): Promise<void> {
  iniciando.value = true
  await notificacao.executar(() => store.iniciar(props.terceiroId, risco.ano))
  iniciando.value = false
}
</script>

<template>
  <SectionCard :titulo="t('analise.titulo')" icone="pi pi-sparkles">
    <template #extra>
      <Tag v-if="analise" :value="analise.modelo" severity="secondary" icon="pi pi-microchip-ai" class="!text-xs" />
    </template>

    <!-- ação -->
    <div class="flex flex-wrap items-center gap-3">
      <Button
        :label="rodando ? t('analise.analisando') : analise ? t('analise.reanalisar') : t('analise.analisar')"
        icon="pi pi-sparkles"
        :loading="iniciando || rodando"
        :disabled="!temDocumentos"
        @click="analisar"
      />
      <span class="text-sm text-surface-500">
        {{ temDocumentos ? t('analise.descricao', { ano: risco.ano }) : t('analise.semDocumentos') }}
      </span>
    </div>

    <!-- em andamento: progresso por documento -->
    <div v-if="analise && rodando" class="space-y-2">
      <div class="flex justify-between text-sm">
        <span>{{ analise.status === 'pendente' ? t('analise.naFila') : t('analise.lendo') }}</span>
        <span class="tabular-nums">{{ analise.progresso.processados }}/{{ analise.progresso.total }}</span>
      </div>
      <ProgressBar :value="percentual" class="!h-2" :show-value="false" />
    </div>

    <!-- falhou -->
    <Message v-if="analise?.status === 'falhou'" severity="error">
      {{ t('analise.falhou') }}
      <div class="text-xs mt-1">{{ analise.erro }}</div>
    </Message>

    <!-- resultado -->
    <template v-if="analise?.resultado">
      <div class="flex flex-wrap items-center gap-4 p-4 rounded-lg bg-surface-50 border border-surface-200">
        <div>
          <div class="text-xs text-surface-500">{{ t('risco.score') }}</div>
          <div class="text-4xl font-bold tabular-nums">{{ Number(analise.resultado.score).toFixed(1) }}</div>
        </div>
        <NivelTag :nivel="analise.resultado.nivel" class="!text-sm" />
        <div class="ml-auto text-right">
          <div class="text-xs text-surface-500">{{ t('analise.recomendacaoTitulo') }}</div>
          <Tag
            :value="t(`analise.recomendacao.${analise.resultado.recomendacao}`)"
            :severity="SEVERIDADE_RECOMENDACAO[analise.resultado.recomendacao]"
            class="!text-sm"
          />
        </div>
      </div>

      <Message v-if="analise.resultado.eliminatorio" severity="error" icon="pi pi-ban">
        {{ t('analise.alertaEliminatorio') }}
      </Message>

      <!-- métricas -->
      <dl class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <div class="p-3 rounded-md bg-surface-50">
          <dt class="text-xs text-surface-500">{{ t('analise.metricas.duracao') }}</dt>
          <dd class="font-semibold tabular-nums">{{ formatarDuracao(analise.metricas.duracao_ms, locale) }}</dd>
        </div>
        <div class="p-3 rounded-md bg-surface-50">
          <dt class="text-xs text-surface-500">{{ t('analise.metricas.espera') }}</dt>
          <dd class="font-semibold tabular-nums">{{ formatarDuracao(analise.metricas.espera_ms, locale) }}</dd>
        </div>
        <div class="p-3 rounded-md bg-surface-50">
          <dt class="text-xs text-surface-500">{{ t('analise.metricas.achados') }}</dt>
          <dd class="font-semibold tabular-nums">
            {{ analise.metricas.achados_validos }}
            <span class="text-xs font-normal text-surface-500">{{ t('analise.metricas.descartados', { n: analise.metricas.achados_descartados }) }}</span>
          </dd>
        </div>
        <div class="p-3 rounded-md bg-surface-50">
          <dt class="text-xs text-surface-500">{{ t('analise.metricas.concluidaEm') }}</dt>
          <dd class="font-semibold">{{ analise.metricas.concluida_em ? formatarDataHora(analise.metricas.concluida_em, locale) : '—' }}</dd>
        </div>
      </dl>

      <!-- porquê do score -->
      <details class="group border border-surface-200 rounded-md" open>
        <summary class="px-3 py-2 cursor-pointer font-medium flex items-center gap-2 list-none">
          <i class="pi pi-calculator text-primary" /> {{ t('analise.porque') }}
          <i class="pi pi-chevron-down text-xs text-surface-400 ml-auto transition-transform group-open:rotate-180" />
        </summary>
        <div class="px-3 pb-3">
          <ExplicacaoScore :explicacao="analise.resultado.explicacao" />
        </div>
      </details>
    </template>

    <!-- documentos lidos (durante e depois) -->
    <div v-if="analise?.documentos.length" class="space-y-2">
      <h3 class="text-sm font-medium">{{ t('analise.documentos') }}</h3>
      <DocumentoAnalisadoItem v-for="doc in analise.documentos" :key="doc.id" :documento="doc" />
    </div>
  </SectionCard>
</template>
