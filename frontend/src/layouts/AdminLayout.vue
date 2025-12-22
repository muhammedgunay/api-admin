<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useTablesStore } from '../stores/tables'
import { useRouter } from 'vue-router'

const tablesStore = useTablesStore()
const { tables, loading } = storeToRefs(tablesStore)
const router = useRouter()

onMounted(() => {
  tablesStore.fetchTables()
})

const logout = () => {
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<template>
  <div class="layout">
    <aside class="sidebar">
      <div class="logo">
        <h3>⚡ Low-Code Platform</h3>
      </div>

      <nav class="menu" v-if="!loading">
        <!-- Sabit Menü -->
        <div class="menu-section">
          <div class="section-title">Yönetim</div>
          <ul>
            <li>
              <router-link to="/" class="menu-link">
                <span class="icon">📊</span>
                Dashboard
              </router-link>
            </li>
            <li>
              <router-link to="/users" class="menu-link">
                <span class="icon">👥</span>
                Kullanıcılar
              </router-link>
            </li>
            <li>
              <router-link to="/roles" class="menu-link">
                <span class="icon">🔐</span>
                Roller
              </router-link>
            </li>
            <li>
              <router-link to="/permissions" class="menu-link">
                <span class="icon">✅</span>
                Yetkiler
              </router-link>
            </li>
            <li>
              <router-link to="/columns" class="menu-link">
                <span class="icon">📊</span>
                Kolonlar
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Dinamik Tablolar -->
        <div class="menu-section">
          <div class="section-title">
            Tablolar
            <router-link to="/tables" class="add-btn" title="Yeni Tablo">+</router-link>
          </div>

          <div v-if="loading" class="loading">Yükleniyor...</div>

          <ul v-else-if="tables.length > 0">
            <li v-for="table in tables" :key="table.id">
              <router-link :to="`/table/${table.name}`" class="menu-link table-link">
                <span class="icon">📋</span>
                {{ table.display_name }}
              </router-link>
            </li>
          </ul>

          <p v-else class="empty-state">Henüz tablo yok</p>
        </div>
      </nav>

      <div class="sidebar-footer">
        <button @click="logout" class="logout-btn">
          <span class="icon">🚪</span>
          Çıkış Yap
        </button>
      </div>
    </aside>

    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
}

.sidebar {
  width: 260px;
  background: #1f2937;
  color: white;
  display: flex;
  flex-direction: column;
  border-right: 1px solid #374151;
}

.logo {
  padding: 20px;
  border-bottom: 1px solid #374151;
}

.logo h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.menu {
  flex: 1;
  overflow-y: auto;
  padding: 16px 0;
}

.menu-section {
  margin-bottom: 24px;
}

.section-title {
  padding: 8px 20px;
  font-size: 11px;
  text-transform: uppercase;
  color: #9ca3af;
  font-weight: 600;
  letter-spacing: 0.5px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.add-btn {
  background: #374151;
  color: white;
  width: 20px;
  height: 20px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  text-decoration: none;
  transition: all 0.2s;
}

.add-btn:hover {
  background: #4b5563;
}

.menu ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.menu li {
  padding: 0 12px;
}

.menu-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  color: #d1d5db;
  text-decoration: none;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s;
}

.menu-link:hover {
  background: #374151;
  color: white;
}

.menu-link.router-link-active {
  background: #3b82f6;
  color: white;
}

.icon {
  font-size: 16px;
}

.table-link {
  font-size: 13px;
}

.loading,
.empty-state {
  padding: 12px 20px;
  color: #9ca3af;
  font-size: 13px;
  font-style: italic;
}

.sidebar-footer {
  padding: 16px;
  border-top: 1px solid #374151;
}

.logout-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: transparent;
  border: 1px solid #374151;
  border-radius: 6px;
  color: #d1d5db;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.logout-btn:hover {
  background: #374151;
  color: white;
}

.content {
  flex: 1;
  overflow-y: auto;
  background: #f9fafb;
  padding: 24px;
}

/* Scrollbar Styling */
.menu::-webkit-scrollbar {
  width: 6px;
}

.menu::-webkit-scrollbar-track {
  background: #1f2937;
}

.menu::-webkit-scrollbar-thumb {
  background: #374151;
  border-radius: 3px;
}

.menu::-webkit-scrollbar-thumb:hover {
  background: #4b5563;
}
</style>