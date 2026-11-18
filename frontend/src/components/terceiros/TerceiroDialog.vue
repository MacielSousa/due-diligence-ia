<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import SelectButton from 'primevue/selectbutton'
import Checkbox from 'primevue/checkbox'
import CampoErro from '@/components/ui/CampoErro.vue'
import { useTerceirosStore } from '@/stores/terceiros'
import { useNotificacao } from '@/composables/useNotificacao'
import type { Terceiro, TerceiroPayload, TipoTerceiro } from '@/types/terceiro'
import { documentoValido } from '@/utils/documento'
import type { ErrosValidacao } from '@/types/api'

interface FormTerceiro {
  tipo: TipoTerceiro
  nome: string
  documento: string
  pep: boolean
}

/** Criação (terceiro = null) e edição do terceiro. */
const visible = defineModel<boolean>('visible', { default: false })
const props = withDefaults(defineProps<{ terceiro?: Terceiro | null }>(), { terceiro: null })
const emit = defineEmits<{ salvo: [terceiro: Terceiro] }>()

const { t } = useI18n()
const store = useTerceirosStore()
const notificacao = useNotificacao()

const vazio = (): FormTerceiro => ({ tipo: 'pf', nome: '', documento: '', pep: false })
const form = ref<FormTerceiro>(vazio())
const erros = ref<ErrosValidacao>({})
const salvando = ref(false)

const editando = computed(() => props.terceiro !== null)
const mascara = computed(() => (form.value.tipo === 'pj' ? '99.999.999/9999-99' : '999.999.999-99'))
const opcoesTipo = computed(() => [
  { label: t('tipo.pf'), value: 'pf' },
  { label: t('tipo.pj'), value: 'pj' },
])

// toda vez que abrir, preenche com o terceiro (edição) ou limpa (criação)
watch(visible, (aberto) => {
  if (!aberto) return
  erros.value = {}
  const original = props.terceiro
  form.value = original
    ? { tipo: original.tipo, nome: original.nome, documento: original.documento, pep: original.pep }
    : vazio()
})

watch(
  () => form.value.tipo,
  (novo, antigo) => {
    if (antigo && novo !== antigo && !editando.value) form.value.documento = ''
  },
)

async function salvar(): Promise<void> {
  // checagem no navegador antes de chamar a API (a API valida de novo)
  if (!documentoValido(form.value.tipo, form.value.documento)) {
    erros.value = { ...erros.value, documento: [t(form.value.tipo === 'pj' ? 'validacao.cnpjInvalido' : 'validacao.cpfInvalido')] }
    return
  }

  salvando.value = true
  const { tipo, ...resto } = form.value
  const payload: TerceiroPayload = editando.value ? resto : { tipo, ...resto }
  const resposta = await notificacao.executar(() => store.salvar(payload, props.terceiro?.id), { erros })
  salvando.value = false

  if (resposta) {
    emit('salvo', resposta.data)
    visible.value = false
  }
}
</script>

<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="editando ? t('terceiro.editar') : t('terceiro.novo')"
    :style="{ width: '32rem' }"
    :breakpoints="{ '575px': '95vw' }"
  >
    <form class="flex flex-col gap-4" @submit.prevent="salvar">
      <SelectButton
        v-if="!editando"
        v-model="form.tipo"
        :options="opcoesTipo"
        option-label="label"
        option-value="value"
        :allow-empty="false"
        class="w-full"
      />

      <div class="flex flex-col gap-1">
        <label for="nome" class="text-sm font-medium">{{ t('terceiro.nome') }}</label>
        <InputText id="nome" v-model="form.nome" :invalid="!!erros.nome" autofocus />
        <CampoErro :erros="erros.nome" />
      </div>

      <div class="flex flex-col gap-1">
        <label for="documento" class="text-sm font-medium">
          {{ form.tipo === 'pj' ? t('terceiro.cnpj') : t('terceiro.cpf') }}
        </label>
        <!-- :key força recriar o campo quando a máscara muda -->
        <InputMask
          id="documento"
          :key="mascara"
          v-model="form.documento"
          :mask="mascara"
          :invalid="!!erros.documento?.length"
          @update:model-value="erros.documento = []"
        />
        <CampoErro :erros="erros.documento" />
      </div>

      <div v-if="form.tipo === 'pf'" class="flex items-center gap-2">
        <Checkbox v-model="form.pep" input-id="pep" binary />
        <label for="pep" class="text-sm">{{ t('terceiro.pepLongo') }}</label>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <Button type="button" :label="t('comum.cancelar')" severity="secondary" text @click="visible = false" />
        <Button type="submit" :label="t('comum.salvar')" icon="pi pi-check" :loading="salvando" />
      </div>
    </form>
  </Dialog>
</template>
