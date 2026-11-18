<script setup lang="ts">
/** Card com título e ícone, usado nas seções das páginas. */
withDefaults(defineProps<{ titulo: string; icone?: string }>(), { icone: '' })
</script>

<template>
  <section class="bg-surface-0 rounded-lg border border-surface-200 p-5 space-y-4">
    <header class="flex items-center justify-between gap-2">
      <h2 class="font-semibold flex items-center gap-2">
        <i v-if="icone" :class="[icone, 'text-primary']" />
        {{ titulo }}
      </h2>
      <slot name="extra" />
    </header>
    <slot />
  </section>
</template>
