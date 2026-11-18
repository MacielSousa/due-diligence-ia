<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { ExplicacaoRisco } from '@/types/analise'

/** "Por que esse score?": cada peso que entrou na conta, na ordem do cálculo. */
defineProps<{ explicacao: ExplicacaoRisco }>()
const { t, te, n } = useI18n()

const rotuloTipo = (tipo: string) => (te(`apontamento.${tipo}`) ? t(`apontamento.${tipo}`) : tipo)
const num = (v: number) => n(v, { maximumFractionDigits: 2 })
</script>

<template>
  <div class="space-y-4 text-sm">
    <!-- 1. apontamentos do próprio terceiro -->
    <div>
      <h4 class="font-medium mb-2">{{ t('analise.explicacao.apontamentos') }}</h4>
      <table v-if="explicacao.apontamentos.length" class="w-full">
        <thead class="text-surface-500 text-xs text-left">
          <tr>
            <th class="py-1 font-medium">{{ t('apontamento.tipo') }}</th>
            <th class="font-medium">{{ t('apontamento.ano') }}</th>
            <th class="font-medium text-right">{{ t('analise.explicacao.pesoBase') }}</th>
            <th class="font-medium text-right">{{ t('analise.explicacao.idade') }}</th>
            <th class="font-medium text-right">{{ t('analise.explicacao.peso') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(a, i) in explicacao.apontamentos" :key="i" class="border-t border-surface-100">
            <td class="py-1.5">{{ rotuloTipo(a.tipo) }}</td>
            <td class="tabular-nums">{{ a.ano }}</td>
            <td class="text-right tabular-nums">{{ a.peso_base }}</td>
            <td class="text-right tabular-nums">
              {{ t('analise.explicacao.anos', { n: a.idade }) }}
              <span v-if="a.depreciado" class="ml-1 text-xs text-orange-600">({{ t('analise.explicacao.metade') }})</span>
            </td>
            <td class="text-right tabular-nums font-medium">+{{ a.peso }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else class="text-surface-500">{{ t('analise.explicacao.semApontamentos') }}</p>
      <p class="text-right mt-1">{{ t('analise.explicacao.riscoProprio') }}: <strong class="tabular-nums">{{ num(explicacao.risco_proprio) }}</strong></p>
    </div>

    <!-- 2. PEP (pessoa física) -->
    <div v-if="explicacao.pep" class="flex justify-between border-t border-surface-100 pt-2">
      <span>{{ t('analise.explicacao.adicionalPep') }}</span>
      <strong class="tabular-nums">+{{ explicacao.adicional_pep }}</strong>
    </div>

    <!-- 3. sócios ponderados pela participação -->
    <div v-if="explicacao.socios.length">
      <h4 class="font-medium mb-2">{{ t('analise.explicacao.socios') }}</h4>
      <table class="w-full">
        <thead class="text-surface-500 text-xs text-left">
          <tr>
            <th class="py-1 font-medium">{{ t('socio.socio') }}</th>
            <th class="font-medium text-right">{{ t('analise.explicacao.scoreSocio') }}</th>
            <th class="font-medium text-right">{{ t('socio.participacao') }}</th>
            <th class="font-medium text-right">{{ t('analise.explicacao.contribuicao') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in explicacao.socios" :key="s.nome" class="border-t border-surface-100">
            <td class="py-1.5">
              {{ s.nome }}
              <span v-if="s.explicacao.pep" class="ml-1 text-xs text-orange-600">PEP</span>
            </td>
            <td class="text-right tabular-nums">{{ num(s.score_socio) }}</td>
            <td class="text-right tabular-nums">× {{ num(s.participacao) }}%</td>
            <td class="text-right tabular-nums font-medium">+{{ num(s.contribuicao) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 4. total -->
    <div class="border-t-2 border-surface-200 pt-2 space-y-1">
      <div class="flex justify-between">
        <span>{{ t('analise.explicacao.soma') }}</span>
        <span class="tabular-nums">{{ num(explicacao.soma) }}</span>
      </div>
      <div v-if="explicacao.teto_aplicado" class="flex justify-between text-orange-600">
        <span>{{ t('analise.explicacao.teto') }}</span>
        <span class="tabular-nums">100</span>
      </div>
      <div class="flex justify-between text-base font-semibold">
        <span>{{ t('analise.explicacao.scoreFinal') }}</span>
        <span class="tabular-nums">{{ num(explicacao.score) }}</span>
      </div>
      <p v-if="explicacao.faixas" class="text-xs text-surface-500">
        {{ t('analise.explicacao.faixas') }}:
        <span v-for="(faixa, nivel) in explicacao.faixas" :key="nivel" class="mr-2">{{ t(`nivel.${nivel}`) }} {{ faixa }}</span>
      </p>
    </div>
  </div>
</template>
