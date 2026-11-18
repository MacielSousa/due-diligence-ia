<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Message from 'primevue/message'
import type { ApiError } from '@/composables/useApi'

/**
 * Mostra um ApiError no lugar do conteúdo (padrão para erros de CARREGAMENTO).
 * Erros de AÇÃO (salvar, excluir) vão para toast via useNotificacao.
 */
const props = withDefaults(defineProps<{ erro: ApiError; size?: 'small' | 'large' }>(), { size: undefined })

const { t } = useI18n()

// domínio incompleto é "esperado" no desafio -> amarelo; o resto é vermelho
const severidade = computed(() => (props.erro.code === 'domain_incomplete' ? 'warn' : 'error'))
const texto = computed(() => (props.erro.semConexao ? t('comum.semConexao') : props.erro.message))
</script>

<template>
  <Message :severity="severidade" :size="size">
    {{ texto }}
    <div v-if="erro.detail" class="text-xs mt-1 font-mono break-all opacity-80">{{ erro.detail }}</div>
  </Message>
</template>
