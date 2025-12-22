import { createRouter, createWebHistory } from 'vue-router'

import Login from '../views/Login.vue'
import AdminLayout from '../layouts/AdminLayout.vue'

import Dashboard from '../views/Dashboard.vue'
import Users from '../views/Users.vue'
import Roles from '../views/Roles.vue'
import Permissions from '../views/Permissions.vue'
import Tables from '../views/Tables.vue'
import Columns from '../views/Columns.vue'
import TableDetail from '../views/TableDetail.vue'

const routes = [
  {
    path: '/login',
    component: Login,
  },

  {
    path: '/',
    component: AdminLayout,
    children: [
      { path: '', component: Dashboard },
      { path: 'users', component: Users },
      { path: 'roles', component: Roles },
      { path: 'permissions', component: Permissions },
      { path: 'tables', component: Tables },
      { path: 'columns', component: Columns },

      // ✅ TABLE DETAIL (Kolon yönetimi için)
      // /tables/3 → Columns.vue açılır
      {
        path: 'tables/:id',
        component: Columns,
      },
      // ✅ DYNAMIC TABLE DETAIL (Dinamik tablo verilerini göstermek için)
      // /table/product → TableDetail.vue açılır
      {
        path: 'table/:name',
        component: TableDetail,
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

/* 🔐 AUTH KONTROL */
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')

  // login değilse ve login sayfası değilse
  if (!token && to.path !== '/login') {
    next('/login')
  } else {
    next()
  }
})

export default router
