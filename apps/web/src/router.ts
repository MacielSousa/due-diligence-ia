import { createRouter, createWebHistory } from 'vue-router'
import FornecedoresView from './views/FornecedoresView.vue'
import FornecedorView from './views/FornecedorView.vue'
import AvaliacaoView from './views/AvaliacaoView.vue'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'fornecedores', component: FornecedoresView },
    { path: '/fornecedores/:id', name: 'fornecedor', component: FornecedorView, props: (r) => ({ id: Number(r.params.id) }) },
    { path: '/avaliacoes/:id', name: 'avaliacao', component: AvaliacaoView, props: (r) => ({ id: Number(r.params.id) }) },
  ],
})
