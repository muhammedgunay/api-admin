<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { getAllColumns, createColumn } from '../api/column'
import { getTables } from '../api/tables'
import AddModal from '../components/AddModal.vue'

const router = useRouter()

const columns = ref([])
const tables = ref([])
const loading = ref(false)
const showAddModal = ref(false)
const submitting = ref(false)
const searchQuery = ref('')

// Kolon tipi seçenekleri
const columnTypes = [
  { value: 'string', label: 'Metin (String)' },
  { value: 'integer', label: 'Sayı (Integer)' },
  { value: 'boolean', label: 'Doğru/Yanlış (Boolean)' },
  { value: 'text', label: 'Uzun Metin (Text)' },
  { value: 'date', label: 'Tarih (Date)' },
  { value: 'datetime', label: 'Tarih-Saat (DateTime)' },
  { value: 'decimal', label: 'Ondalıklı Sayı (Decimal)' },
]

// Form alanları
const formFields = computed(() => [
  {
    name: 'table_id',
    label: 'Tablo',
    type: 'select',
    required: true,
    options: tables.value.map(table => ({
      value: table.id,
      label: `${table.display_name} (${table.name})`,
    })),
  },
  {
    name: 'name',
    label: 'Kolon Adı',
    type: 'text',
    required: true,
    placeholder: 'ornek_kolon_adi (snake_case)',
  },
  {
    name: 'display_name',
    label: 'Görünen Ad',
    type: 'text',
    required: true,
    placeholder: 'Örnek Kolon Adı',
  },
  {
    name: 'type',
    label: 'Veri Tipi',
    type: 'select',
    required: true,
    options: columnTypes,
  },
  {
    name: 'is_visible',
    label: 'Görünür',
    type: 'select',
    required: false,
    value: 'true',
    options: [
      { value: 'true', label: 'Evet' },
      { value: 'false', label: 'Hayır' },
    ],
  },
  {
    name: 'is_editable',
    label: 'Düzenlenebilir',
    type: 'select',
    required: false,
    value: 'true',
    options: [
      { value: 'true', label: 'Evet' },
      { value: 'false', label: 'Hayır' },
    ],
  },
])

// Tüm kolonları çek
const fetchColumns = async () => {
  loading.value = true
  try {
    const response = await getAllColumns()
    columns.value = response.data
  } catch (error) {
    console.error('Kolonlar yüklenemedi:', error)
    alert(error.response?.data?.message || 'Kolonlar yüklenirken bir hata oluştu')
  } finally {
    loading.value = false
  }
}

// Tüm tabloları çek
const fetchTables = async () => {
  try {
    const response = await getTables()
    tables.value = Array.isArray(response.data) ? response.data : (response.data?.data ?? [])
  } catch (error) {
    console.error('Tablolar yüklenemedi:', error)
  }
}

onMounted(async () => {
  await Promise.all([fetchTables(), fetchColumns()])
})

const openAddModal = () => {
  if (tables.value.length === 0) {
    alert('Lütfen önce bir tablo oluşturun')
    router.push('/tables')
    return
  }
  showAddModal.value = true
}

const closeAddModal = () => {
  showAddModal.value = false
}

const handleSubmit = async (formData) => {
  submitting.value = true
  
  try {
    // FormData'dan boolean değerleri düzelt
    const data = {
      ...formData,
      table_id: parseInt(formData.table_id),
      is_visible: formData.is_visible === 'true' || formData.is_visible === true,
      is_editable: formData.is_editable === 'true' || formData.is_editable === true,
    }
    
    await createColumn(data.table_id, data)
    await fetchColumns()
    closeAddModal()
  } catch (error) {
    console.error('Kolon ekleme hatası:', error)
    alert(error.response?.data?.message || 'Kolon eklenirken bir hata oluştu')
  } finally {
    submitting.value = false
  }
}

const deleteColumn = async (column) => {
  if (!confirm(`"${column.display_name}" kolonunu silmek istediğinize emin misiniz?`)) {
    return
  }
  
  try {
    // Delete API call will be added when needed
    alert('Silme işlemi henüz implement edilmedi')
  } catch (error) {
    console.error('Kolon silme hatası:', error)
    alert(error.response?.data?.message || 'Kolon silinirken bir hata oluştu')
  }
}

// Filtrelenmiş kolonlar
const filteredColumns = computed(() => {
  if (!searchQuery.value.trim()) {
    return columns.value
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return columns.value.filter(column => {
    const displayName = (column.display_name || '').toLowerCase()
    const name = (column.name || '').toLowerCase()
    const tableName = (column.table?.display_name || column.table?.name || '').toLowerCase()
    const type = (column.type || '').toLowerCase()
    
    return displayName.includes(query) || 
           name.includes(query) || 
           tableName.includes(query) ||
           type.includes(query)
  })
})

const clearSearch = () => {
  searchQuery.value = ''
}
</script>

<template>
  <div class="columns-page">
    <div class="header">
      <div>
        <h1>Kolonlar</h1>
        <p class="description">Sistemdeki tüm kolonları yönetin</p>
      </div>
      <button class="btn-primary" @click="openAddModal">
        + Yeni Kolon
      </button>
    </div>

    <!-- Arama/Filtre -->
    <div v-if="!loading && columns.length > 0" class="search-section">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Kolon ara... (isim, görünen ad, tablo veya tip)"
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
        {{ filteredColumns.length }} kolon bulundu
      </div>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      Yükleniyor...
    </div>

    <div v-else-if="columns.length === 0" class="empty-state">
      <div class="empty-icon">📊</div>
      <p>Henüz kolon yok</p>
      <button class="btn-primary" @click="openAddModal">İlk Kolonu Ekle</button>
    </div>

    <div v-else-if="filteredColumns.length === 0" class="empty-state">
      <div class="empty-icon">🔍</div>
      <p>"{{ searchQuery }}" için sonuç bulunamadı</p>
      <button class="btn-secondary" @click="clearSearch">Aramayı Temizle</button>
    </div>

    <div v-else class="columns-table-wrapper">
      <table class="columns-table">
        <thead>
          <tr>
            <th>Tablo</th>
            <th>Kolon Adı</th>
            <th>Görünen Ad</th>
            <th>Tip</th>
            <th>Görünür</th>
            <th>Düzenlenebilir</th>
            <th class="actions-col">İşlemler</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="column in filteredColumns" :key="column.id">
            <td>
              <span class="table-badge">
                {{ column.table?.display_name || column.table?.name || '-' }}
              </span>
            </td>
            <td>
              <code class="column-name">{{ column.name }}</code>
            </td>
            <td>{{ column.display_name }}</td>
            <td>
              <span class="type-badge">{{ column.type }}</span>
            </td>
            <td>
              <span :class="['status-badge', column.is_visible ? 'visible' : 'hidden']">
                {{ column.is_visible ? '✅ Evet' : '❌ Hayır' }}
              </span>
            </td>
            <td>
              <span :class="['status-badge', column.is_editable ? 'editable' : 'not-editable']">
                {{ column.is_editable ? '✅ Evet' : '❌ Hayır' }}
              </span>
            </td>
            <td class="actions-col">
              <button class="btn-icon" title="Düzenle">✏️</button>
              <button class="btn-icon" title="Sil" @click="deleteColumn(column)">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add Modal -->
    <AddModal
      :show="showAddModal"
      title="Yeni Kolon Ekle"
      :fields="formFields"
      :loading="submitting"
      @close="closeAddModal"
      @submit="handleSubmit"
    />
  </div>
</template>

<style scoped>
.columns-page {
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

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 16px;
}

.btn-secondary:hover {
  background: #e5e7eb;
  color: #111827;
}

.columns-table-wrapper {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.columns-table {
  width: 100%;
  border-collapse: collapse;
}

.columns-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.columns-table th {
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.columns-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f3f4f6;
  color: #1f2937;
  font-size: 14px;
}

.columns-table tbody tr:last-child td {
  border-bottom: none;
}

.columns-table tbody tr:hover {
  background: #f9fafb;
}

.table-badge {
  background: #eff6ff;
  color: #1e40af;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.column-name {
  background: #f3f4f6;
  color: #374151;
  padding: 4px 8px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  font-size: 13px;
}

.type-badge {
  background: #f0fdf4;
  color: #166534;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  font-family: 'Courier New', monospace;
}

.status-badge {
  font-size: 13px;
}

.status-badge.visible,
.status-badge.editable {
  color: #059669;
}

.status-badge.hidden,
.status-badge.not-editable {
  color: #dc2626;
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
</style>