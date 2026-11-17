<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { api, ApiError } from '../api'
import DimensaoBarra from '../components/DimensaoBarra.vue'
import NivelBadge from '../components/NivelBadge.vue'
import { data, percentual, rotuloDecisao } from '../formatar'
import type { Avaliacao, ChamadaLlm } from '../types'

const props = defineProps<{ id: number }>()

const avaliacao = ref<Avaliacao>()
const auditoria = ref<ChamadaLlm[] | null>(null)
const erro = ref('')
const decisao = reactive({ decisao: 'aprovado_com_ressalvas', decidido_por: '', justificativa: '' })
let timer: ReturnType<typeof setTimeout> | undefined

const resultado = computed(() => avaliacao.value?.resultado ?? null)
const emAndamento = computed(() => ['pendente', 'processando'].includes(avaliacao.value?.status ?? ''))
const achados = computed(() => resultado.value?.analises_documentos.flatMap((a) => a.achados) ?? [])

// Avaliação roda num job da fila: consulta até concluir.
async function carregar() {
  avaliacao.value = await api.avaliacao(props.id)
  if (emAndamento.value) timer = setTimeout(carregar, 1500)
}

async function verAuditoria() {
  auditoria.value = await api.auditoria(props.id)
}

async function decidir() {
  erro.value = ''
  try {
    avaliacao.value = { ...avaliacao.value!, ...(await api.decidir(props.id, decisao)) }
  } catch (e) {
    erro.value = e instanceof ApiError ? Object.values(e.errors)[0]?.[0] ?? e.message : String(e)
  }
}

onMounted(carregar)
onUnmounted(() => clearTimeout(timer))
</script>

<template>
  <template v-if="avaliacao">
    <RouterLink :to="{ name: 'fornecedor', params: { id: avaliacao.fornecedor_id } }" class="suave">
      ← {{ avaliacao.fornecedor?.razao_social }}
    </RouterLink>
    <div class="linha">
      <h1>Avaliação #{{ avaliacao.id }}</h1>
      <NivelBadge :nivel="avaliacao.nivel" :status="avaliacao.status" />
    </div>

    <section v-if="emAndamento" class="cartao">
      <p>Avaliação na fila (Redis) — o worker está lendo os documentos e consultando o modelo…</p>
    </section>

    <section v-else-if="avaliacao.status === 'falhou'" class="cartao">
      <p class="erro">A avaliação falhou: {{ avaliacao.erro }}</p>
      <p class="suave">Encaminhe para análise manual ou tente novamente.</p>
    </section>

    <template v-else-if="resultado">
      <div class="grade">
        <section class="cartao">
          <h2>Parecer</h2>
          <div class="linha">
            <span>Score <strong>{{ resultado.score }}</strong>/100</span>
            <span>Confiança <strong>{{ percentual(resultado.confianca) }}</strong></span>
            <span v-if="resultado.requer_revisao_humana" class="selo medio">Revisão humana obrigatória</span>
          </div>
          <p>{{ resultado.resumo_executivo }}</p>
          <p><strong>Recomendação:</strong> {{ resultado.recomendacao }}</p>
          <h2>Por que essa classificação</h2>
          <ul>
            <li v-for="m in resultado.motivos" :key="m">{{ m }}</li>
          </ul>
        </section>

        <section class="cartao">
          <h2>Risco por dimensão</h2>
          <DimensaoBarra v-for="d in resultado.dimensoes" :key="d.dimensao" :dimensao="d" />
        </section>
      </div>

      <section v-if="resultado.sinais_eliminatorios.length" class="cartao">
        <h2>Sinais eliminatórios</h2>
        <div v-for="s in resultado.sinais_eliminatorios" :key="s.tipo + s.documento_id">
          <strong class="erro">{{ s.descricao }}</strong>
          <div class="evidencia">“{{ s.evidencia }}”</div>
        </div>
      </section>

      <section class="cartao">
        <h2>Achados com evidência ({{ achados.length }})</h2>
        <p v-if="!achados.length" class="suave">Nenhum ponto de atenção encontrado nos documentos.</p>
        <table v-else>
          <thead><tr><th>Dimensão</th><th>Severidade</th><th>Achado e evidência literal</th></tr></thead>
          <tbody>
            <tr v-for="a in achados" :key="a.id">
              <td>{{ a.dimensao }}</td>
              <td>{{ a.severidade }}</td>
              <td>{{ a.descricao }}<div class="evidencia">“{{ a.evidencia }}”</div></td>
            </tr>
          </tbody>
        </table>
        <p v-if="resultado.documentos_faltantes.length" class="suave">
          Documentos obrigatórios ausentes: {{ resultado.documentos_faltantes.join(', ') }}
        </p>
      </section>

      <section class="cartao">
        <h2>Decisão do comitê</h2>
        <p v-if="avaliacao.decisao">
          <strong>{{ rotuloDecisao[avaliacao.decisao] }}</strong> por {{ avaliacao.decidido_por }} em {{ data(avaliacao.decidido_em) }}<br />
          <span class="suave">{{ avaliacao.justificativa_decisao }}</span>
        </p>
        <form v-else @submit.prevent="decidir">
          <label>Decisão
            <select v-model="decisao.decisao">
              <option value="aprovado">Aprovado</option>
              <option value="aprovado_com_ressalvas">Aprovado com ressalvas</option>
              <option value="reprovado">Reprovado</option>
            </select>
          </label>
          <label>Responsável <input v-model="decisao.decidido_por" required /></label>
          <label>Justificativa <textarea v-model="decisao.justificativa" required minlength="10" /></label>
          <span v-if="erro" class="erro">{{ erro }}</span>
          <button>Registrar decisão</button>
        </form>
      </section>

      <section class="cartao">
        <div class="linha entre">
          <h2>Auditoria das chamadas ao LLM</h2>
          <button v-if="!auditoria" class="secundario" @click="verAuditoria">Carregar (MongoDB)</button>
        </div>
        <details v-for="c in auditoria ?? []" :key="c.id">
          <summary>
            {{ c.etapa === 'consolidacao' ? 'Consolidação' : 'Análise de documento' }} · {{ c.modelo }} ·
            {{ c.duracao_ms }} ms<template v-if="c.tokens_entrada"> · {{ c.tokens_entrada }}/{{ c.tokens_saida }} tokens</template>
          </summary>
          <pre>{{ c.prompt }}</pre>
          <pre>{{ JSON.stringify(c.resposta, null, 2) }}</pre>
        </details>
      </section>
    </template>
  </template>
</template>
