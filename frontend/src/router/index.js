import { createRouter, createWebHistory } from 'vue-router'

import Login from '../views/Login.vue'
import AdminLayout from '../layouts/AdminLayout.vue'

import Dashboard from '../views/Dashboard.vue'
import Users from '../views/Users.vue'
import Roles from '../views/Roles.vue'
import Permissions from '../views/Permissions.vue'
import Tables from '../views/Tables.vue'
import Columns from '../views/Columns.vue'

const routes = [
  { path: '/login', component: Login },

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
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
