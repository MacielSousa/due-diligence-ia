<script setup lang="ts">
/** Cabeçalho padrão das páginas: título, descrição e área de ações. */
withDefaults(defineProps<{ titulo: string; descricao?: string }>(), { descricao: '' })
</script>

<template>
  <div class="space-y-3">
    <slot name="antes" />
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div class="min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-2xl font-semibold">{{ titulo }}</h1>
          <slot name="tags" />
        </div>
        <p v-if="descricao" class="text-surface-500 text-sm">{{ descricao }}</p>
        <slot name="info" />
      </div>
      <div v-if="$slots.acoes" class="flex flex-wrap items-end gap-2">
        <slot name="acoes" />
      </div>
    </div>
  </div>
</template>
