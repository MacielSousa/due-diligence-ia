<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, ApiError } from '../api'
import { moeda } from '../formatar'
import type { Fornecedor } from '../types'

const router = useRouter()
const fornecedores = ref<Fornecedor[]>([])
const carregando = ref(true)
const enviando = ref(false)
const erros = ref<Record<string, string[]>>({})
const form = reactive({ razao_social: '', cnpj: '', valor_contrato_estimado: '' })

async function carregar() {
  carregando.value = true
  fornecedores.value = await api.fornecedores()
  carregando.value = false
}

async function cadastrar() {
  enviando.value = true
  erros.value = {}
  try {
    const novo = await api.criarFornecedor({
      razao_social: form.razao_social,
      cnpj: form.cnpj,
      valor_contrato_estimado: form.valor_contrato_estimado || null,
    })
    await router.push({ name: 'fornecedor', params: { id: novo.id } })
  } catch (e) {
    erros.value = e instanceof ApiError ? e.errors : { geral: [String(e)] }
  } finally {
    enviando.value = false
  }
}

onMounted(carregar)
</script>

<template>
  <h1>Fornecedores</h1>
  <p class="suave">Cadastre a construtora, anexe os documentos e solicite a avaliação de risco.</p>

  <div class="grade">
    <section class="cartao">
      <h2>Fornecedores cadastrados</h2>
      <p v-if="carregando" class="suave">Carregando…</p>
      <p v-else-if="!fornecedores.length" class="suave">Nenhum fornecedor. Rode <code>sail artisan db:seed</code> para a demo.</p>
      <table v-else>
        <thead><tr><th>Razão social</th><th>CNPJ</th><th>Contrato</th></tr></thead>
        <tbody>
          <tr v-for="f in fornecedores" :key="f.id">
            <td><RouterLink :to="{ name: 'fornecedor', params: { id: f.id } }">{{ f.razao_social }}</RouterLink></td>
            <td>{{ f.cnpj_formatado }}</td>
            <td>{{ moeda(f.valor_contrato_estimado) }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="cartao">
      <h2>Novo fornecedor</h2>
      <form @submit.prevent="cadastrar">
        <label>Razão social
          <input v-model="form.razao_social" required />
        </label>
        <label>CNPJ
          <input v-model="form.cnpj" required placeholder="00.000.000/0001-00" />
          <span v-if="erros.cnpj" class="erro">{{ erros.cnpj[0] }}</span>
        </label>
        <label>Valor estimado do contrato (R$)
          <input v-model="form.valor_contrato_estimado" type="number" min="0" step="0.01" />
        </label>
        <span v-if="erros.geral" class="erro">{{ erros.geral[0] }}</span>
        <button :disabled="enviando">{{ enviando ? 'Salvando…' : 'Cadastrar' }}</button>
      </form>
    </section>
  </div>
</template>
