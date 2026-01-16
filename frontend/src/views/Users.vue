<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'

const users = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)

// Kullanıcıları çek
const fetchUsers = async () => {
  loading.value = true
  error.value = null
  
  try {
    const res = await api.get(`/users?page=${currentPage.value}`)
    
    // Pagination kontrolü
    if (res.data.data) {
      users.value = res.data.data
      currentPage.value = res.data.current_page
      totalPages.value = res.data.last_page
    } else {
      users.value = res.data
    }
    
  } catch (e) {
    error.value = e.response?.data?.message || 'Kullanıcılar yüklenemedi'
    console.error('Kullanıcı yükleme hatası:', e)
  } finally {
    loading.value = false
  }
}

// Sayfa değiştir
const changePage = (page) => {
  currentPage.value = page
  fetchUsers()
}

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div class="users-page">
    <div class="header">
      <div>
        <h1>Kullanıcılar</h1>
        <p class="description">Sistemdeki tüm kullanıcıları görüntüleyin</p>
      </div>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      Yükleniyor...
    </div>

    <div v-else-if="error" class="error-box">
      ❌ {{ error }}
    </div>

    <div v-else-if="users.length === 0" class="empty-state">
      <div class="empty-icon">👥</div>
      <p>Henüz kullanıcı yok</p>
    </div>

    <div v-else>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Ad</th>
              <th>E-posta</th>
              <th>Roller</th>
              <th>Oluşturulma</th>
              <th class="actions-col">İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <span v-if="user.roles && user.roles.length > 0" class="role-badge">
                  {{ user.roles.map(r => r.name).join(', ') }}
                </span>
                <span v-else class="no-role">Rol yok</span>
              </td>
              <td>{{ new Date(user.created_at).toLocaleDateString('tr-TR') }}</td>
              <td class="actions-col">
                <button class="btn-icon" title="Düzenle">✏️</button>
                <button class="btn-icon" title="Sil">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button 
          @click="changePage(currentPage - 1)" 
          :disabled="currentPage === 1"
          class="page-btn"
        >
          ← Önceki
        </button>
        
        <span class="page-info">
          Sayfa {{ currentPage }} / {{ totalPages }}
        </span>
        
        <button 
          @click="changePage(currentPage + 1)" 
          :disabled="currentPage === totalPages"
          class="page-btn"
        >
          Sonraki →
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.users-page {
  max-width: 1400px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  gap: 20px;
}

.header h1 {
  margin: 0 0 4px 0;
  font-size: 28px;
  color: #111827;
}

.description {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 48px;
  color: #6b7280;
}

.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-box {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 16px;
  border-radius: 8px;
}

.empty-state {
  text-align: center;
  padding: 64px 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.empty-state p {
  color: #6b7280;
  font-size: 16px;
  margin-bottom: 24px;
}

.table-wrapper {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  overflow: hidden;
  margin-bottom: 16px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.data-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f3f4f6;
  color: #1f2937;
  font-size: 14px;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f9fafb;
}

.actions-col {
  width: 120px;
  text-align: center;
}

.btn-icon {
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 18px;
  padding: 4px 8px;
  transition: transform 0.2s;
}

.btn-icon:hover {
  transform: scale(1.2);
}

.role-badge {
  display: inline-block;
  background: #dbeafe;
  color: #1e40af;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.no-role {
  color: #9ca3af;
  font-size: 12px;
  font-style: italic;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.page-btn {
  padding: 8px 16px;
  background: #f3f4f6;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  background: #e5e7eb;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  color: #6b7280;
  font-size: 14px;
  min-width: 120px;
  text-align: center;
}
</style>
