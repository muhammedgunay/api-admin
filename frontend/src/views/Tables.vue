<script setup>
import { ref, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useTablesStore } from '../stores/tables'
import { useRouter } from 'vue-router'
import { createTable, deleteTable as deleteTableApi, fixTable } from '../api/tables'
import AddModal from '../components/AddModal.vue'

const tablesStore = useTablesStore()
const { tables, loading } = storeToRefs(tablesStore)
const router = useRouter()

const showAddModal = ref(false)
const submitting = ref(false)
const searchQuery = ref('')

const formFields = [
  {
    name: 'name',
    label: 'Tablo Adı',
    type: 'text',
    required: true,
    placeholder: 'ornek_tablo_adi (snake_case)',
  },
  {
    name: 'display_name',
    label: 'Görünen Ad',
    type: 'text',
    required: true,
    placeholder: 'Örnek Tablo Adı',
  },
  {
    name: 'description',
    label: 'Açıklama',
    type: 'textarea',
    required: false,
    placeholder: 'Tablo hakkında açıklama...',
  },
]

onMounted(() => {
  tablesStore.fetchTables()
})

const openAddModal = () => {
  showAddModal.value = true
}

const closeAddModal = () => {
  showAddModal.value = false
}

const handleSubmit = async (formData) => {
  submitting.value = true
  
  try {
    await createTable(formData)
    // Store'u güncelle
    await tablesStore.fetchTables()
    closeAddModal()
  } catch (error) {
    console.error('Tablo ekleme hatası:', error)
    alert(error.response?.data?.message || 'Tablo eklenirken bir hata oluştu')
  } finally {
    submitting.value = false
  }
}

const goToColumns = (tableId) => {
  router.push(`/tables/${tableId}`)
}

const goToTableDetail = (tableName) => {
  router.push(`/table/${tableName}`)
}

const deleteTable = async (table) => {
  if (!confirm(`"${table.display_name}" tablosunu silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz ve tablodaki tüm kolonlar da silinecektir.`)) {
    return
  }
  
  try {
    await deleteTableApi(table.id)
    // Store'u güncelle
    await tablesStore.fetchTables()
  } catch (error) {
    console.error('Tablo silme hatası:', error)
    alert(error.response?.data?.message || 'Tablo silinirken bir hata oluştu')
  }
}

const fixTableAction = async (table) => {
  try {
    await fixTable(table.id)
    alert('Tablo başarıyla düzeltildi. Veritabanı tablosu oluşturuldu.')
  } catch (error) {
    console.error('Tablo düzeltme hatası:', error)
    alert(error.response?.data?.message || 'Tablo düzeltilirken bir hata oluştu')
  }
}

// Filtrelenmiş tablolar
const filteredTables = computed(() => {
  if (!searchQuery.value.trim()) {
    return tables.value
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return tables.value.filter(table => {
    const displayName = (table.display_name || '').toLowerCase()
    const name = (table.name || '').toLowerCase()
    const description = (table.description || '').toLowerCase()
    
    return displayName.includes(query) || 
           name.includes(query) || 
           description.includes(query)
  })
})

const clearSearch = () => {
  searchQuery.value = ''
}
</script>

<template>
  <div class="tables-page">
    <div class="header">
      <div>
        <h1>Tablolar</h1>
        <p class="description">Sistemdeki tüm tabloları yönetin</p>
      </div>
      <button class="btn-primary" @click="openAddModal">
        + Yeni Tablo
      </button>
    </div>

    <!-- Arama/Filtre -->
    <div v-if="!loading && tables.length > 0" class="search-section">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tablo ara... (isim, görünen ad veya açıklama)"
          class="search-input"
        />
        <button
          v-if="searchQuery"
          @click="clearSearch"
          class="clear-search-btn"
          title="Temizle"
        >
          ×
        </button>
      </div>
      <div v-if="searchQuery" class="search-results-info">
        {{ filteredTables.length }} tablo bulundu
      </div>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      Yükleniyor...
    </div>

    <div v-else-if="tables.length === 0" class="empty-state">
      <div class="empty-icon">📋</div>
      <p>Henüz tablo yok</p>
      <button class="btn-primary" @click="openAddModal">İlk Tabloyu Ekle</button>
    </div>

    <div v-else-if="filteredTables.length === 0" class="empty-state">
      <div class="empty-icon">🔍</div>
      <p>"{{ searchQuery }}" için sonuç bulunamadı</p>
      <button class="btn-secondary" @click="clearSearch">Aramayı Temizle</button>
    </div>

    <div v-else class="tables-grid">
      <div
        v-for="table in filteredTables"
        :key="table.id"
        class="table-card"
      >
        <div class="table-card-header">
          <h3>{{ table.display_name }}</h3>
          <span class="table-name">{{ table.name }}</span>
        </div>
        
        <p v-if="table.description" class="table-description">
          {{ table.description }}
        </p>
        
        <div class="table-card-footer">
          <div class="footer-actions">
            <button
              class="btn-secondary"
              @click="goToTableDetail(table.name)"
              title="Tablo Verilerini Görüntüle"
            >
              📊 Veriler
            </button>
            <button
              v-if="table.id"
              class="btn-secondary"
              @click="goToColumns(table.id)"
              title="Kolonları Yönet"
            >
              ⚙️ Kolonlar
            </button>
          </div>
          <div class="footer-menu">
            <button
              class="btn-icon-small"
              @click="fixTableAction(table)"
              title="Veritabanı Tablosunu Oluştur/Düzelt"
            >
              🔧
            </button>
            <button
              class="btn-icon-small btn-danger"
              @click="deleteTable(table)"
              title="Tablo Sil"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Modal -->
    <AddModal
      :show="showAddModal"
      title="Yeni Tablo Ekle"
      :fields="formFields"
      :loading="submitting"
      @close="closeAddModal"
      @submit="handleSubmit"
    />
  </div>
</template>

<style scoped>
.tables-page {
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

.search-section {
  margin-bottom: 24px;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0 12px;
  transition: all 0.2s;
  max-width: 500px;
}

.search-box:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-icon {
  font-size: 18px;
  color: #9ca3af;
  margin-right: 8px;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  padding: 12px 8px;
  font-size: 14px;
  color: #111827;
  background: transparent;
}

.search-input::placeholder {
  color: #9ca3af;
}

.clear-search-btn {
  background: transparent;
  border: none;
  font-size: 24px;
  color: #9ca3af;
  cursor: pointer;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
  padding: 0;
  line-height: 1;
}

.clear-search-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.search-results-info {
  margin-top: 8px;
  font-size: 13px;
  color: #6b7280;
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
  to {
    transform: rotate(360deg);
  }
}

.empty-state {
  text-align: center;
  padding: 64px 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

.tables-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.table-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 20px;
  transition: all 0.2s;
}

.table-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.table-card-header {
  margin-bottom: 12px;
}

.table-card-header h3 {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.table-name {
  font-size: 12px;
  color: #6b7280;
  font-family: 'Courier New', monospace;
  background: #f3f4f6;
  padding: 2px 8px;
  border-radius: 4px;
}

.table-description {
  color: #6b7280;
  font-size: 14px;
  margin: 12px 0;
  line-height: 1.5;
}

.table-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.footer-actions {
  display: flex;
  gap: 8px;
  flex: 1;
}

.footer-menu {
  display: flex;
  gap: 4px;
}

.btn-secondary {
  flex: 1;
  background: #f3f4f6;
  color: #374151;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon-small {
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 16px;
  padding: 6px 8px;
  border-radius: 6px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-icon-small:hover {
  background: #f3f4f6;
}

.btn-icon-small.btn-danger:hover {
  background: #fef2f2;
  color: #dc2626;
}

.btn-secondary:hover {
  background: #e5e7eb;
  color: #111827;
}

.empty-state .btn-secondary {
  flex: none;
  margin-top: 16px;
  padding: 10px 20px;
  font-size: 14px;
}
</style>