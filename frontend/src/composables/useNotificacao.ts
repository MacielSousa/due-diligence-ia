import type { Ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { isApiError } from './useApi'
import type { ApiSucesso, ErrosValidacao } from '@/types/api'

export interface OpcoesExecucao {
  /** ref que recebe os erros de validação por campo (422) */
  erros?: Ref<ErrosValidacao>
  /** não mostra o toast de sucesso */
  silencioso?: boolean
}

/**
 * PADRÃO de tratamento do retorno da API na interface:
 *  - sucesso  -> toast com a `message` que veio do backend (se houver)
 *  - erro     -> toast com a `message` do backend + erros por campo no formulário
 *  - sem rede -> mensagem traduzida "backend está rodando?"
 *
 * (precisa ser chamado dentro do setup de um componente)
 */
export function useNotificacao() {
  const { t } = useI18n()
  const toast = useToast()
  const confirm = useConfirm()

  function mensagemDeErro(erro: unknown): string {
    if (isApiError(erro)) return erro.semConexao ? t('comum.semConexao') : erro.message
    return erro instanceof Error ? erro.message : t('comum.erro')
  }

  function sucesso(mensagem?: string | null): void {
    if (!mensagem) return
    toast.add({ severity: 'success', summary: t('comum.sucesso'), detail: mensagem, life: 3000 })
  }

  function erro(e: unknown): void {
    toast.add({ severity: 'error', summary: t('comum.erro'), detail: mensagemDeErro(e), life: 5000 })
  }

  /**
   * Executa uma ação que chama a API aplicando o padrão de mensagens.
   * Devolve a resposta em caso de sucesso, ou null se deu erro (já notificado).
   */
  async function executar<T>(acao: () => Promise<ApiSucesso<T>>, opcoes: OpcoesExecucao = {}): Promise<ApiSucesso<T> | null> {
    if (opcoes.erros) opcoes.erros.value = {}
    try {
      const resposta = await acao()
      if (!opcoes.silencioso) sucesso(resposta.message)
      return resposta
    } catch (e) {
      if (opcoes.erros && isApiError(e)) opcoes.erros.value = e.errors
      erro(e)
      return null
    }
  }

  /** Pede confirmação e, se aceito, executa a exclusão com o mesmo padrão de mensagens. */
  function confirmarExclusao<T>(nome: string, acao: () => Promise<ApiSucesso<T>>, aoConcluir?: () => void): void {
    confirm.require({
      header: t('comum.confirmar'),
      message: t('comum.confirmarExclusao', { nome }),
      icon: 'pi pi-exclamation-triangle',
      rejectProps: { label: t('comum.cancelar'), severity: 'secondary', outlined: true },
      acceptProps: { label: t('comum.excluir'), severity: 'danger' },
      accept: async () => {
        const resposta = await executar(acao)
        if (resposta) aoConcluir?.()
      },
    })
  }

  return { sucesso, erro, executar, confirmarExclusao, mensagemDeErro }
}
