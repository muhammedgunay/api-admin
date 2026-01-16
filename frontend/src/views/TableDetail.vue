<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/axios'
import { createRecord } from '../api/generic'
import AddModal from '../components/AddModal.vue'

const route = useRoute()
const tableName = route.params.name

const tableData = ref([])
const tableInfo = ref(null)
const columns = ref([])
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)

// Modal için state'ler
const showAddModal = ref(false)
const submitting = ref(false)

// Tablo bilgilerini çek
const fetchTableInfo = async () => {
  try {
    // Hem Table hem DynamicTable'dan ara
    const [tablesRes, dynamicTablesRes] = await Promise.allSettled([
      api.get('/tables'),
      api.get('/dynamic-tables'),
    ])

    let table = null

    // Önce Table modelinde ara
    if (tablesRes.status === 'fulfilled') {
      const tableData = tablesRes.value.data.data ?? tablesRes.value.data
      table = Array.isArray(tableData) ? tableData.find(t => t.name === tableName) : null
    }

    // Bulunamazsa DynamicTable'da ara
    if (!table && dynamicTablesRes.status === 'fulfilled') {
      const dynamicTableData = dynamicTablesRes.value.data.data ?? dynamicTablesRes.value.data
      const found = Array.isArray(dynamicTableData) ? dynamicTableData.find(t => t.name === tableName) : null
      if (found) {
        table = {
          ...found,
          display_name: found.label || found.name, // label'ı display_name'e çevir
        }
      }
    }

    if (table) {
      tableInfo.value = table
      columns.value = table.columns || []
    }
  } catch (e) {
    console.error('Tablo bilgisi yüklenemedi:', e)
  }
}

// Tablo verilerini çek
const fetchData = async () => {
  loading.value = true
  error.value = null
  
  try {
    const res = await api.get(`/${tableName}?page=${currentPage.value}`)
    
    // Pagination kontrolü
    if (res.data.data) {
      tableData.value = res.data.data
      currentPage.value = res.data.current_page
      totalPages.value = res.data.last_page
    } else {
      tableData.value = res.data
    }
    
  } catch (e) {
    error.value = e.response?.data?.message || 'Veriler yüklenemedi'
    console.error('Veri yükleme hatası:', e)
  } finally {
    loading.value = false
  }
}

// Görünür kolonlar
const visibleColumns = computed(() => {
  return columns.value.filter(col => col.is_visible)
})

// Form field'larını kolonlardan oluştur
const formFields = computed(() => {
  return columns.value
    .filter(col => col.name !== 'id' && col.name !== 'created_at' && col.name !== 'updated_at')
    .map(col => {
      const field = {
        name: col.name,
        label: col.display_name,
        required: col.is_required,
        placeholder: col.display_name,
      }

      // Kolon tipine göre input tipi belirle
      switch (col.type) {
        case 'string':
        case 'varchar':
        case 'char':
          field.type = 'text'
          break
        case 'text':
          field.type = 'textarea'
          break
        case 'integer':
        case 'bigint':
        case 'smallint':
          field.type = 'number'
          break
        case 'decimal':
        case 'float':
        case 'double':
          field.type = 'number'
          break
        case 'boolean':
          field.type = 'select'
          field.options = [
            { value: true, label: 'Evet' },
            { value: false, label: 'Hayır' }
          ]
          break
        case 'date':
          field.type = 'date'
          break
        case 'datetime':
        case 'timestamp':
          field.type = 'datetime-local'
          break
        case 'email':
          field.type = 'email'
          break
        default:
          field.type = 'text'
      }

      return field
    })
})

// Modal açma/kapama fonksiyonları
const openAddModal = () => {
  showAddModal.value = true
}

const closeAddModal = () => {
  showAddModal.value = false
}

// Yeni kayıt ekleme
const handleSubmit = async (formData) => {
  submitting.value = true
  
  try {
    await createRecord(tableName, formData)
    await fetchData()
    closeAddModal()
  } catch (e) {
    alert(e.response?.data?.message || 'Kayıt eklenirken bir hata oluştu')
  } finally {
    submitting.value = false
  }
}

// Kayıt sil
const deleteRecord = async (id) => {
  if (!confirm('Bu kaydı silmek istediğinize emin misiniz?')) return
  
  try {
    await api.delete(`/${tableName}/${id}`)
    await fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Silme işlemi başarısız')
  }
}

// Sayfa değiştir
const changePage = (page) => {
  currentPage.value = page
  fetchData()
}

onMounted(async () => {
  await fetchTableInfo()
  await fetchData()
})
</script>

<template>
  <div class="table-detail">
    <div class="header">
      <div>
        <h1>{{ tableInfo?.display_name || tableName }}</h1>
        <p v-if="tableInfo?.description" class="description">
          {{ tableInfo.description }}
        </p>
      </div>
      <button class="btn-primary" @click="openAddModal">+ Yeni Kayıt</button>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      Yükleniyor...
    </div>

    <div v-else-if="error" class="error-box">
      ❌ {{ error }}
    </div>

    <div v-else-if="tableData.length === 0" class="empty-state">
      <div class="empty-icon">📭</div>
      <p>Henüz kayıt yok</p>
      <button class="btn-primary" @click="openAddModal">İlk Kaydı Ekle</button>
    </div>

    <div v-else>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th v-for="col in visibleColumns" :key="col.id">
                {{ col.display_name }}
              </th>
              <th class="actions-col">İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in tableData" :key="row.id">
              <td v-for="col in visibleColumns" :key="col.id">
                <template v-if="col.type === 'boolean'">
                  {{ row[col.name] ? '✅' : '❌' }}
                </template>
                <template v-else-if="col.type === 'date'">
                  {{ row[col.name] ? new Date(row[col.name]).toLocaleDateString('tr-TR') : '-' }}
                </template>
                <template v-else>
                  {{ row[col.name] || '-' }}
                </template>
              </td>
              <td class="actions-col">
                <button class="btn-icon" title="Düzenle">✏️</button>
                <button class="btn-icon" title="Sil" @click="deleteRecord(row.id)">🗑️</button>
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

    <!-- Add Modal -->
    <AddModal
      :show="showAddModal"
      :title="`Yeni ${tableInfo?.display_name || tableName} Kaydı`"
      :fields="formFields"
      :loading="submitting"
      @close="closeAddModal"
      @submit="handleSubmit"
    />
  </div>
</template>

<style scoped>
.table-detail {
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
  text-transform: capitalize;
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