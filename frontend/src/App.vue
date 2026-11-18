<script setup lang="ts">
import { watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePrimeVue } from 'primevue/config'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const { t, locale } = useI18n()
const primevue = usePrimeVue()

// textos internos dos componentes do PrimeVue acompanham o idioma do i18n
watch(
  locale,
  () => {
    if (!primevue.config.locale) return
    Object.assign(primevue.config.locale, {
      noFileChosenMessage: t('primevue.nenhumArquivo'),
      emptyFilterMessage: t('primevue.semResultados'),
      emptySearchMessage: t('primevue.semResultados'),
      emptyMessage: t('primevue.semOpcoes'),
      accept: t('comum.sim'),
      reject: t('comum.nao'),
    })
  },
  { immediate: true },
)
</script>

<template>
  <!-- O layout (DefaultLayout) é escolhido pela rota; aqui ficam só os serviços globais -->
  <RouterView />
  <Toast position="bottom-right" />
  <ConfirmDialog />
</template>
