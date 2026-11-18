<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Tag from 'primevue/tag'
import type { DocumentoAnalisado } from '@/types/analise'
import { formatarDuracao } from '@/utils/formatar'

/** Um documento lido pela IA: status, tempo e os achados com a evidência (ou o motivo do descarte). */
const props = defineProps<{ documento: DocumentoAnalisado }>()
const { t, te, locale } = useI18n()

const rotuloTipo = (tipo: string) => (te(`apontamento.${tipo}`) ? t(`apontamento.${tipo}`) : tipo)
const icone = computed(
  () =>
    ({
      pendente: 'pi pi-clock text-surface-400',
      processando: 'pi pi-spin pi-spinner text-primary',
      concluido: 'pi pi-check-circle text-green-600',
      ignorado: 'pi pi-minus-circle text-orange-500',
    })[props.documento.status],
)
const validos = computed(() => props.documento.achados.filter((a) => a.valido).length)
</script>

<template>
  <details class="group border border-surface-200 rounded-md" :open="documento.achados.length > 0">
    <summary class="flex items-center gap-2 px-3 py-2 cursor-pointer select-none list-none">
      <i :class="icone" />
      <span class="font-medium grow truncate">{{ documento.nome }}</span>
      <span class="text-xs text-surface-500 hidden sm:inline">{{ t(`anexo.categorias.${documento.categoria}`) }}</span>
      <span class="text-xs tabular-nums text-surface-500 w-16 text-right">{{ formatarDuracao(documento.duracao_ms, locale) }}</span>
      <Tag
        v-if="documento.status === 'concluido'"
        :value="t('analise.achadosCount', { n: validos })"
        :severity="validos ? 'warn' : 'success'"
        class="!text-xs"
      />
      <i class="pi pi-chevron-down text-xs text-surface-400 transition-transform group-open:rotate-180" />
    </summary>

    <div class="px-3 pb-3 space-y-2 text-sm">
      <p v-if="documento.erro" class="text-orange-600">{{ documento.erro }}</p>
      <p v-else-if="documento.status === 'concluido' && !documento.achados.length" class="text-surface-500">
        {{ t('analise.semAchados') }}
      </p>

      <div
        v-for="(a, i) in documento.achados"
        :key="i"
        class="rounded-md p-2 border-l-4"
        :class="a.valido ? 'border-l-green-500 bg-green-50' : 'border-l-surface-300 bg-surface-50 opacity-80'"
      >
        <div class="flex flex-wrap items-center gap-2">
          <i :class="a.valido ? 'pi pi-check text-green-600' : 'pi pi-times text-surface-500'" />
          <strong>{{ rotuloTipo(a.tipo) }}</strong>
          <span v-if="a.ano" class="tabular-nums">{{ a.ano }}</span>
          <span v-if="a.envolvido" class="text-surface-500">· {{ a.envolvido }}</span>
          <Tag v-if="a.valido && a.eliminatorio" :value="t('analise.eliminatorio')" severity="danger" class="!text-xs" />
          <Tag v-if="a.valido && !a.entra_no_score" :value="t('analise.informativo')" severity="secondary" class="!text-xs" />
        </div>
        <blockquote class="mt-1 pl-3 border-l-2 border-surface-300 italic text-surface-700">"{{ a.trecho }}"</blockquote>
        <p v-if="!a.valido" class="mt-1 text-xs text-surface-600"><i class="pi pi-info-circle mr-1" />{{ t('analise.descartado') }}: {{ a.motivo }}</p>
      </div>
    </div>
  </details>
</template>
