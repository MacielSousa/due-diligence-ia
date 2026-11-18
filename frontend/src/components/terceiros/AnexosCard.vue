<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import Select from 'primevue/select'
import FileUpload, { type FileUploadSelectEvent } from 'primevue/fileupload'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import SectionCard from '@/components/ui/SectionCard.vue'
import CampoErro from '@/components/ui/CampoErro.vue'
import { useTerceirosStore } from '@/stores/terceiros'
import { useNotificacao } from '@/composables/useNotificacao'
import { CATEGORIAS_ANEXO, type Anexo, type AnexoPayload } from '@/types/terceiro'
import type { ErrosValidacao } from '@/types/api'
import { formatarBytes, formatarDataHora, iconeArquivo } from '@/utils/formatar'

/** Documentos anexados ao terceiro aberto (contrato social, certidões...). */
const TAMANHO_MAXIMO = 2 * 1024 * 1024 // 2 MB, igual ao AnexoRequest do backend
const TIPOS_ACEITOS = '.pdf,.jpg,.jpeg,.png'

const { t, locale } = useI18n()
const store = useTerceirosStore()
const { atual } = storeToRefs(store)
const notificacao = useNotificacao()

const vazio = (): AnexoPayload => ({ categoria: null, arquivo: null })
const form = ref<AnexoPayload>(vazio())
const erros = ref<ErrosValidacao>({})
const enviando = ref(false)
// os tipos do PrimeVue não declaram o clear(), que existe no componente: tipamos só o que usamos
const seletor = ref<{ clear: () => void } | null>(null)

const opcoes = computed(() => CATEGORIAS_ANEXO.map((c) => ({ label: t(`anexo.categorias.${c}`), value: c })))

function aoSelecionar(evento: FileUploadSelectEvent): void {
  const arquivo = (evento.files as File[])[0] ?? null
  erros.value = { ...erros.value, arquivo: [] }

  // checagem rápida no navegador; a API valida de novo
  if (arquivo && arquivo.size > TAMANHO_MAXIMO) {
    erros.value.arquivo = [t('anexo.muitoGrande', { max: '2 MB' })]
    seletor.value?.clear()
    form.value.arquivo = null
    return
  }
  form.value.arquivo = arquivo
}

async function enviar(): Promise<void> {
  enviando.value = true
  const resposta = await notificacao.executar(() => store.adicionarAnexo(form.value), { erros })
  enviando.value = false

  if (resposta) {
    form.value = vazio()
    seletor.value?.clear()
  }
}

function remover(anexo: Anexo): void {
  notificacao.confirmarExclusao(anexo.nome, () => store.removerAnexo(anexo.id))
}
</script>

<template>
  <SectionCard v-if="atual" :titulo="t('anexo.titulo')" icone="pi pi-paperclip">
    <form class="flex flex-wrap items-start gap-2" @submit.prevent="enviar">
      <div class="flex flex-col gap-1 grow min-w-52">
        <Select v-model="form.categoria" :options="opcoes" option-label="label" option-value="value" :placeholder="t('anexo.categoria')" :invalid="!!erros.categoria?.length" />
        <CampoErro :erros="erros.categoria" />
      </div>
      <div class="flex flex-col gap-1">
        <FileUpload
          ref="seletor"
          mode="basic"
          :accept="TIPOS_ACEITOS"
          :custom-upload="true"
          :auto="false"
          :choose-label="t('anexo.escolher')"
          :class="{ 'p-invalid': erros.arquivo?.length }"
          @select="aoSelecionar"
        />
        <CampoErro :erros="erros.arquivo" class="max-w-60" />
      </div>
      <Button type="submit" :label="t('anexo.enviar')" icon="pi pi-upload" :loading="enviando" />
    </form>
    <p class="text-xs text-surface-500">{{ t('anexo.dica', { max: '2 MB' }) }}</p>

    <DataTable :value="atual.anexos" data-key="id" size="small">
      <template #empty>{{ t('anexo.nenhum') }}</template>
      <Column :header="t('anexo.arquivo')">
        <template #body="{ data }">
          <a :href="data.download_url" class="text-primary hover:underline inline-flex items-center gap-2" :title="t('anexo.baixar')">
            <i :class="iconeArquivo(data.mime)" /> {{ data.nome }}
          </a>
        </template>
      </Column>
      <Column :header="t('anexo.categoria')">
        <template #body="{ data }">{{ t(`anexo.categorias.${data.categoria}`) }}</template>
      </Column>
      <Column :header="t('anexo.tamanho')">
        <template #body="{ data }"><span class="tabular-nums">{{ formatarBytes(data.tamanho, locale) }}</span></template>
      </Column>
      <Column :header="t('anexo.enviadoEm')">
        <template #body="{ data }">{{ formatarDataHora(data.enviado_em, locale) }}</template>
      </Column>
      <Column class="w-16 text-right">
        <template #body="{ data }">
          <Button icon="pi pi-trash" severity="danger" text rounded size="small" :aria-label="t('comum.excluir')" @click="remover(data)" />
        </template>
      </Column>
    </DataTable>
  </SectionCard>
</template>
