import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import i18n from '@/i18n'

/**
 * Todas as páginas são filhas do DefaultLayout: header, menu e rodapé ficam
 * montados e só o <RouterView /> do layout troca de página.
 * (os campos de `meta` estão tipados em src/types/router.d.ts)
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: DefaultLayout,
    children: [
      {
        path: '',
        name: 'relatorio',
        component: () => import('@/pages/RelatorioPage.vue'),
        meta: { titulo: 'nav.relatorio', menu: true, icone: 'pi pi-chart-bar', ordem: 1 },
      },
      {
        path: 'terceiros',
        name: 'terceiros',
        component: () => import('@/pages/TerceirosPage.vue'),
        meta: { titulo: 'nav.terceiros', menu: true, icone: 'pi pi-users', ordem: 2 },
      },
      {
        path: 'terceiros/:id(\\d+)',
        name: 'terceiro',
        component: () => import('@/pages/TerceiroDetalhePage.vue'),
        props: true, // o :id chega como prop
        meta: { titulo: 'terceiro.detalhe', menuPai: 'terceiros' },
      },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'relatorio' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.afterEach((to) => {
  const { t } = i18n.global
  document.title = to.meta.titulo ? `${t(to.meta.titulo)} · ${t('app.titulo')}` : t('app.titulo')
})

export default router
