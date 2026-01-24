<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api/axios'
import AddModal from '../components/AddModal.vue'

const users = ref([])
const roles = ref([])
const columns = ref([]) // Dinamik kolonlar
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)

// Modal Durumları
const showModal = ref(false)
const modalLoading = ref(false)
const modalTitle = ref('')
const currentUser = ref(null)

// Form Alanları Tanımı (AddModal için)
const modalFields = ref([])

// Kolonları çek
const fetchColumns = async () => {
  try {
    const res = await api.get('/users/columns')
    columns.value = res.data.columns
  } catch (e) {
    console.error('Kolonlar yüklenemedi:', e)
  }
}

// Kullanıcıları çek
const fetchUsers = async () => {
  loading.value = true
  error.value = null
  
  try {
    const res = await api.get(`/users?page=${currentPage.value}`)
    
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

// Rolleri çek
const fetchRoles = async () => {
  try {
    const res = await api.get('/roles')
    roles.value = res.data
  } catch (e) {
    console.error('Roller yüklenemedi:', e)
  }
}

// Kolon tipini belirle
const getFieldType = (columnName) => {
  if (columnName === 'password') return 'password'
  if (columnName === 'email') return 'email'
  return 'text'
}

// Kolon label'ını oluştur (Türkçeleştirme)
const getFieldLabel = (columnName) => {
  const labels = {
    'id': 'ID',
    'name': 'Ad',
    'surname': 'Soyad',
    'email': 'E-posta',
    'password': 'Şifre',
    'created_at': 'Oluşturulma',
    'updated_at': 'Güncellenme',
    'created_by': 'Oluşturan',
    'updated_by': 'Güncelleyen'
  }
  return labels[columnName] || columnName.charAt(0).toUpperCase() + columnName.slice(1).replace(/_/g, ' ')
}

// Modal Form Alanlarını Dinamik Olarak Hazırla
const prepareModalFields = (user = null) => {
  const roleOptions = roles.value.map(r => ({ label: r.name, value: r.name }))
  
  const fields = []
  
  // Audit kolonları - formda gösterilmemeli
  const auditColumns = ['id', 'created_at', 'updated_at', 'created_by', 'updated_by']
  
  // Kolonları dinamik olarak form alanlarına çevir
  columns.value.forEach(col => {
    // ID, timestamp ve audit kolonlarını formdan çıkar
    if (auditColumns.includes(col)) {
      return
    }
    
    fields.push({
      name: col,
      label: getFieldLabel(col),
      type: getFieldType(col),
      required: ['name', 'email'].includes(col),
      value: user?.[col] || ''
    })
  })
  
  // Password alanını manuel ekle (backend'den gelmiyor çünkü güvenlik)
  fields.push({
    name: 'password',
    label: 'Şifre',
    type: 'password',
    required: !user, // Yeni kullanıcıda zorunlu, düzenlemede opsiyonel
    placeholder: user ? 'Boş bırakılırsa değişmez' : '',
    value: ''
  })
  
  // Roller alanını ekle (bu bir kolon değil, ilişki)
  fields.push({
    name: 'roles',
    label: 'Roller',
    type: 'select',
    options: roleOptions,
    value: user && user.roles && user.roles.length > 0 ? user.roles[0].name : ''
  })
  
  return fields
}

// Görünür kolonları hesapla (tablo için)
const visibleColumns = computed(() => {
  return columns.value.filter(col => {
    // ID, created_at, updated_at her zaman göster
    return true
  })
})

// Yeni Kullanıcı Ekle
const openAddModal = () => {
  currentUser.value = null
  modalTitle.value = 'Yeni Kullanıcı Ekle'
  modalFields.value = prepareModalFields()
  showModal.value = true
}

// Düzenle
const openEditModal = (user) => {
  currentUser.value = user
  modalTitle.value = 'Kullanıcı Düzenle'
  modalFields.value = prepareModalFields(user)
  showModal.value = true
}

// Modal Submit
const handleSave = async (formData) => {
  modalLoading.value = true
  try {
    const payload = { ...formData }
    if (payload.roles && payload.roles !== '') {
      payload.roles = [payload.roles]
    } else {
      payload.roles = []
    }

    if (!payload.password && currentUser.value) {
      delete payload.password
    }

    if (currentUser.value) {
      await api.put(`/users/${currentUser.value.id}`, payload)
    } else {
      await api.post('/users', payload)
    }

    await fetchUsers()
    showModal.value = false
  } catch (e) {
    alert(e.response?.data?.message || 'Kaydetme başarısız')
  } finally {
    modalLoading.value = false
  }
}

// Sil
const deleteUser = async (user) => {
  if (!confirm(`${user.name} kullanıcısını silmek istediğinize emin misiniz?`)) return

  try {
    await api.delete(`/users/${user.id}`)
    await fetchUsers()
  } catch (e) {
    alert(e.response?.data?.message || 'Silme işlemi başarısız')
  }
}

// Sayfa değiştir
const changePage = (page) => {
  currentPage.value = page
  fetchUsers()
}

onMounted(async () => {
  await fetchColumns()
  await fetchRoles()
  await fetchUsers()
})
</script>

<template>
  <div class="users-page">
    <div class="header">
      <div>
        <h1>Kullanıcılar</h1>
        <p class="description">Sistemdeki tüm kullanıcıları görüntüleyin ve yönetin</p>
      </div>
      <button class="btn-primary" @click="openAddModal">
        + Yeni Kullanıcı
      </button>
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
      <button class="btn-primary" @click="openAddModal">İlk Kullanıcıyı Oluştur</button>
    </div>

    <div v-else>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th v-for="col in visibleColumns" :key="col">
                {{ getFieldLabel(col) }}
              </th>
              <th>Roller</th>
              <th class="actions-col">İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td v-for="col in visibleColumns" :key="col">
                <template v-if="col === 'created_at' || col === 'updated_at'">
                  {{ user[col] ? new Date(user[col]).toLocaleDateString('tr-TR') : '-' }}
                </template>
                <template v-else>
                  {{ user[col] || '-' }}
                </template>
              </td>
              <td>
                <span v-if="user.roles && user.roles.length > 0" class="role-badge">
                  {{ user.roles.map(r => r.name).join(', ') }}
                </span>
                <span v-else class="no-role">Rol yok</span>
              </td>
              <td class="actions-col">
                <button class="btn-icon" title="Düzenle" @click="openEditModal(user)">✏️</button>
                <button class="btn-icon" title="Sil" @click="deleteUser(user)">🗑️</button>
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

    <AddModal
      :show="showModal"
      :title="modalTitle"
      :fields="modalFields"
      :loading="modalLoading"
      @close="showModal = false"
      @submit="handleSave"
    />
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

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover {
  background: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
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
  margin-right: 4px;
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
