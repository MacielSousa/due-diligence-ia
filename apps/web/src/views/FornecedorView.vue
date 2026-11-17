<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, ApiError } from '../api'
import NivelBadge from '../components/NivelBadge.vue'
import { data, moeda, rotuloDecisao } from '../formatar'
import type { Avaliacao, Documento, Fornecedor, TipoDocumento } from '../types'

const props = defineProps<{ id: number }>()
const router = useRouter()

const fornecedor = ref<Fornecedor>()
const documentos = ref<Documento[]>([])
const avaliacoes = ref<Avaliacao[]>([])
const tipos = ref<TipoDocumento[]>([])
const erro = ref('')
const enviando = ref(false)
const avaliando = ref(false)
const form = reactive({ tipo: '', titulo: '', emitido_em: '', conteudo: '' })

const faltantes = computed(() => {
  const presentes = new Set(documentos.value.map((d) => d.tipo))
  return tipos.value.filter((t) => t.obrigatorio && !presentes.has(t.valor))
})

async function carregar() {
  ;[fornecedor.value, documentos.value, avaliacoes.value, tipos.value] = await Promise.all([
    api.fornecedor(props.id),
    api.documentos(props.id),
    api.avaliacoes(props.id),
    api.tiposDocumento(),
  ])
}

async function enviarDocumento() {
  enviando.value = true
  erro.value = ''
  try {
    await api.enviarDocumento(props.id, { ...form, emitido_em: form.emitido_em || undefined })
    Object.assign(form, { tipo: '', titulo: '', emitido_em: '', conteudo: '' })
    documentos.value = await api.documentos(props.id)
  } catch (e) {
    erro.value = e instanceof ApiError ? e.message : String(e)
  } finally {
    enviando.value = false
  }
}

async function avaliar() {
  avaliando.value = true
  erro.value = ''
  try {
    const avaliacao = await api.solicitarAvaliacao(props.id)
    await router.push({ name: 'avaliacao', params: { id: avaliacao.id } })
  } catch (e) {
    erro.value = e instanceof ApiError ? e.message : String(e)
    avaliando.value = false
  }
}

onMounted(carregar)
</script>

<template>
  <template v-if="fornecedor">
    <div class="linha entre">
      <div>
        <RouterLink to="/" class="suave">← Fornecedores</RouterLink>
        <h1>{{ fornecedor.razao_social }}</h1>
        <p class="suave">CNPJ {{ fornecedor.cnpj_formatado }} · Contrato estimado {{ moeda(fornecedor.valor_contrato_estimado) }}</p>
      </div>
      <button :disabled="avaliando || !documentos.length" @click="avaliar">
        {{ avaliando ? 'Enviando para a fila…' : 'Avaliar risco' }}
      </button>
    </div>
    <p v-if="erro" class="erro">{{ erro }}</p>

    <div class="grade">
      <section class="cartao">
        <h2>Documentos ({{ documentos.length }})</h2>
        <p v-if="faltantes.length" class="suave">
          Obrigatórios faltando: {{ faltantes.map((t) => t.rotulo).join(', ') }} — a confiança da avaliação será menor.
        </p>
        <table v-if="documentos.length">
          <thead><tr><th>Tipo</th><th>Título</th><th>Emissão</th></tr></thead>
          <tbody>
            <tr v-for="d in documentos" :key="d.id">
              <td>{{ d.tipo_rotulo }}</td>
              <td>{{ d.titulo }}</td>
              <td>{{ data(d.emitido_em) }}</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="cartao">
        <h2>Anexar documento</h2>
        <form @submit.prevent="enviarDocumento">
          <label>Tipo
            <select v-model="form.tipo" required>
              <option value="" disabled>Selecione…</option>
              <option v-for="t in tipos" :key="t.valor" :value="t.valor">{{ t.rotulo }}{{ t.obrigatorio ? ' *' : '' }}</option>
            </select>
          </label>
          <label>Título <input v-model="form.titulo" required /></label>
          <label>Data de emissão <input v-model="form.emitido_em" type="date" /></label>
          <label>Conteúdo (texto extraído do documento)
            <textarea v-model="form.conteudo" required />
          </label>
          <button :disabled="enviando">{{ enviando ? 'Enviando…' : 'Anexar' }}</button>
        </form>
      </section>
    </div>

    <section class="cartao">
      <h2>Avaliações</h2>
      <p v-if="!avaliacoes.length" class="suave">Nenhuma avaliação ainda.</p>
      <table v-else>
        <thead><tr><th>#</th><th>Data</th><th>Risco</th><th>Score</th><th>Decisão do comitê</th></tr></thead>
        <tbody>
          <tr v-for="a in avaliacoes" :key="a.id">
            <td><RouterLink :to="{ name: 'avaliacao', params: { id: a.id } }">#{{ a.id }}</RouterLink></td>
            <td>{{ data(a.criado_em) }}</td>
            <td><NivelBadge :nivel="a.nivel" :status="a.status" /></td>
            <td>{{ a.score ?? '—' }}</td>
            <td>{{ a.decisao ? rotuloDecisao[a.decisao] : a.requer_revisao ? 'Pendente' : '—' }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </template>
</template>
