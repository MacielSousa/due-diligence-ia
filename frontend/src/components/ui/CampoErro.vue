<script setup lang="ts">
/** Primeira mensagem de validação de um campo (vem do `errors` da API). */
defineProps<{ erros?: string[] }>()
</script>

<template>
  <small v-if="erros?.length" class="text-red-600">{{ erros[0] }}</small>
</template>
