<script setup lang="ts">
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
</script>

<template>
  <div class="min-h-screen flex flex-col bg-surface-50 text-surface-800">
    <AppHeader />

    <main class="flex-1 w-full max-w-6xl mx-auto px-4 py-6">
      <!-- as páginas filhas da rota aparecem aqui -->
      <RouterView />
    </main>

    <AppFooter />
  </div>
</template>
