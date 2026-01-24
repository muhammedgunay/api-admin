<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api/axios'

const filters = ref([])
const tables = ref([])
const placeholders = ref([])
const operators = ref({})
const loading = ref(false)
const error = ref(null)

// Modal Durumları
const showModal = ref(false)
const modalLoading = ref(false)
const modalTitle = ref('')
const currentFilter = ref(null)

// Form State
const formData = ref({
  name: '',
  description: '',
  table_name: '',
  filter_type: 'sql',
  sql_where_clause: '',
  json_rules: null,
  is_active: true
})

// Tablo kolonları (seçilen tabloya göre)
const tableColumns = ref([])

// Test Modal
const showTestModal = ref(false)
const testResult = ref(null)
const testLoading = ref(false)

// Filtreleri çek
const fetchFilters = async () => {
  loading.value = true
  error.value = null
  
  try {
    const res = await api.get('/filters?paginate=false')
    filters.value = res.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Filtreler yüklenemedi'
    console.error('Filtre yükleme hatası:', e)
  } finally {
    loading.value = false
  }
}

// Tabloları çek
const fetchTables = async () => {
  try {
    const res = await api.get('/filters/tables')
    tables.value = res.data.tables
  } catch (e) {
    console.error('Tablolar yüklenemedi:', e)
  }
}

// Placeholder'ları çek
const fetchPlaceholders = async () => {
  try {
    const res = await api.get('/filters/placeholders')
    placeholders.value = res.data.placeholders
    operators.value = res.data.operators
  } catch (e) {
    console.error('Placeholder\'lar yüklenemedi:', e)
  }
}

// Tablo seçildiğinde kolonları çek
const onTableSelect = async (tableName) => {
  if (!tableName) {
    tableColumns.value = []
    return
  }
  
  try {
    const res = await api.get('/filters/table-columns', {
      params: { table_name: tableName }
    })
    tableColumns.value = res.data.columns
  } catch (e) {
    console.error('Kolonlar yüklenemedi:', e)
    tableColumns.value = []
  }
}

// Yeni Filtre Ekle
const openAddModal = () => {
  currentFilter.value = null
  modalTitle.value = 'Yeni Filtre Ekle'
  formData.value = {
    name: '',
    description: '',
    table_name: '',
    filter_type: 'sql',
    sql_where_clause: '',
    json_rules: null,
    is_active: true
  }
  tableColumns.value = []
  showModal.value = true
}

// Düzenle
const openEditModal = (filter) => {
  currentFilter.value = filter
  modalTitle.value = 'Filtre Düzenle'
  formData.value = {
    name: filter.name,
    description: filter.description || '',
    table_name: filter.table_name,
    filter_type: filter.filter_type,
    sql_where_clause: filter.sql_where_clause || '',
    json_rules: filter.json_rules,
    is_active: filter.is_active
  }
  
  // Tablo kolonlarını yükle
  if (filter.table_name) {
    onTableSelect(filter.table_name)
  }
  
  showModal.value = true
}

// Kaydet
const handleSave = async () => {
  modalLoading.value = true
  try {
    const payload = { ...formData.value }
    
    // Filter type'a göre gereksiz alanları temizle
    if (payload.filter_type === 'sql') {
      payload.json_rules = null
    } else {
      payload.sql_where_clause = null
    }
    
    if (currentFilter.value) {
      await api.put(`/filters/${currentFilter.value.id}`, payload)
    } else {
      await api.post('/filters', payload)
    }
    
    await fetchFilters()
    showModal.value = false
  } catch (e) {
    const errorMsg = e.response?.data?.message || 'Kaydetme başarısız'
    alert(errorMsg)
    console.error('Kaydetme hatası:', e.response?.data)
  } finally {
    modalLoading.value = false
  }
}

// Sil
const deleteFilter = async (filter) => {
  if (!confirm(`"${filter.name}" filtresini silmek istediğinize emin misiniz?`)) return

  try {
    await api.delete(`/filters/${filter.id}`)
    await fetchFilters()
  } catch (e) {
    alert(e.response?.data?.message || 'Silme işlemi başarısız')
  }
}

// Test Filtre
const testFilter = async (filter) => {
  testLoading.value = true
  testResult.value = null
  showTestModal.value = true
  
  try {
    const res = await api.post(`/filters/${filter.id}/test`)
    testResult.value = res.data
  } catch (e) {
    testResult.value = {
      error: e.response?.data?.error || 'Test başarısız'
    }
  } finally {
    testLoading.value = false
  }
}

// Placeholder ekle (SQL editor'a)
const insertPlaceholder = (placeholder) => {
  formData.value.sql_where_clause += placeholder
}

onMounted(async () => {
  await fetchTables()
  await fetchPlaceholders()
  await fetchFilters()
})
</script>

<template>
  <div class="filters-page">
    <div class="header">
      <div>
        <h1>Filtreler</h1>
        <p class="description">Dinamik filtreler oluşturun ve yönetin</p>
      </div>
      <button class="btn-primary" @click="openAddModal">
        + Yeni Filtre
      </button>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      Yükleniyor...
    </div>

    <div v-else-if="error" class="error-box">
      ❌ {{ error }}
    </div>

    <div v-else-if="filters.length === 0" class="empty-state">
      <div class="empty-icon">🔍</div>
      <p>Henüz filtre yok</p>
      <button class="btn-primary" @click="openAddModal">İlk Filtreyi Oluştur</button>
    </div>

    <div v-else>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ad</th>
              <th>Tablo</th>
              <th>Tip</th>
              <th>Açıklama</th>
              <th>Durum</th>
              <th class="actions-col">İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="filter in filters" :key="filter.id">
              <td>
                <strong>{{ filter.name }}</strong>
              </td>
              <td>
                <code class="table-badge">{{ filter.table_name }}</code>
              </td>
              <td>
                <span :class="['type-badge', filter.filter_type]">
                  {{ filter.filter_type === 'sql' ? 'SQL' : 'JSON' }}
                </span>
              </td>
              <td>
                <span class="description-text">{{ filter.description || '-' }}</span>
              </td>
              <td>
                <span :class="['status-badge', filter.is_active ? 'active' : 'inactive']">
                  {{ filter.is_active ? 'Aktif' : 'Pasif' }}
                </span>
              </td>
              <td class="actions-col">
                <button class="btn-icon" title="Test Et" @click="testFilter(filter)">🧪</button>
                <button class="btn-icon" title="Düzenle" @click="openEditModal(filter)">✏️</button>
                <button class="btn-icon" title="Sil" @click="deleteFilter(filter)">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ modalTitle }}</h2>
          <button class="close-btn" @click="showModal = false">×</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label>Filtre Adı *</label>
            <input 
              v-model="formData.name" 
              type="text" 
              placeholder="Örn: Sadece Kendi Kayıtları"
              required
            />
          </div>

          <div class="form-group">
            <label>Açıklama</label>
            <textarea 
              v-model="formData.description" 
              placeholder="Filtrenin ne yaptığını açıklayın"
              rows="2"
            ></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Tablo *</label>
              <select 
                v-model="formData.table_name" 
                @change="onTableSelect(formData.table_name)"
                required
              >
                <option value="">Tablo seçin</option>
                <option v-for="table in tables" :key="table" :value="table">
                  {{ table }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Filtre Tipi *</label>
              <select v-model="formData.filter_type" required>
                <option value="sql">SQL WHERE Clause</option>
                <option value="json">JSON Rule Builder</option>
              </select>
            </div>
          </div>

          <!-- SQL Mode -->
          <div v-if="formData.filter_type === 'sql'" class="sql-editor-section">
            <div class="form-group">
              <label>SQL WHERE Clause *</label>
              <div class="editor-toolbar">
                <span class="toolbar-label">Placeholder'lar:</span>
                <button 
                  v-for="ph in placeholders" 
                  :key="ph.name"
                  type="button"
                  class="placeholder-btn"
                  :title="ph.description"
                  @click="insertPlaceholder(ph.name)"
                >
                  {{ ph.name }}
                </button>
              </div>
              <textarea 
                v-model="formData.sql_where_clause"
                class="sql-editor"
                placeholder="Örn: created_by = {user_id}"
                rows="6"
                required
              ></textarea>
              <small class="help-text">
                Örnek: <code>created_by = {user_id} AND status = 'active'</code>
              </small>
            </div>

            <!-- Placeholder Yardım -->
            <div class="placeholder-help">
              <details>
                <summary>📖 Placeholder Kullanım Kılavuzu</summary>
                <div class="placeholder-list">
                  <div v-for="ph in placeholders" :key="ph.name" class="placeholder-item">
                    <code>{{ ph.name }}</code>
                    <span>{{ ph.description }}</span>
                    <small>Örnek: {{ ph.example }}</small>
                  </div>
                </div>
              </details>
            </div>
          </div>

          <!-- JSON Mode (Placeholder for now) -->
          <div v-else class="json-editor-section">
            <div class="form-group">
              <label>JSON Rules</label>
              <div class="info-box">
                <p>🚧 JSON Rule Builder yakında eklenecek.</p>
                <p>Şimdilik SQL mode kullanabilirsiniz.</p>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="formData.is_active" />
              <span>Filtre aktif</span>
            </label>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-secondary" @click="showModal = false" :disabled="modalLoading">
            İptal
          </button>
          <button class="btn-primary" @click="handleSave" :disabled="modalLoading">
            {{ modalLoading ? 'Kaydediliyor...' : 'Kaydet' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Test Modal -->
    <div v-if="showTestModal" class="modal-overlay" @click.self="showTestModal = false">
      <div class="modal test-modal">
        <div class="modal-header">
          <h2>🧪 Filtre Test Sonucu</h2>
          <button class="close-btn" @click="showTestModal = false">×</button>
        </div>

        <div class="modal-body">
          <div v-if="testLoading" class="loading">
            <div class="spinner"></div>
            Test ediliyor...
          </div>

          <div v-else-if="testResult">
            <div v-if="testResult.error" class="error-box">
              ❌ {{ testResult.error }}
            </div>

            <div v-else class="test-result">
              <div class="result-section">
                <h3>Filtre Bilgisi</h3>
                <p><strong>Ad:</strong> {{ testResult.filter_name }}</p>
                <p><strong>Tip:</strong> {{ testResult.filter_type }}</p>
              </div>

              <div class="result-section">
                <h3>Kullanıcı Bağlamı</h3>
                <pre>{{ JSON.stringify(testResult.user_context, null, 2) }}</pre>
              </div>

              <div class="result-section">
                <h3>Orijinal</h3>
                <pre>{{ typeof testResult.original === 'string' ? testResult.original : JSON.stringify(testResult.original, null, 2) }}</pre>
              </div>

              <div class="result-section success">
                <h3>✅ Derlenmiş SQL</h3>
                <pre class="sql-output">{{ testResult.compiled_sql }}</pre>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-primary" @click="showTestModal = false">Kapat</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.filters-page {
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
}

.btn-primary:hover {
  background: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
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
}

.btn-secondary:hover {
  background: #e5e7eb;
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
  margin-bottom: 16px;
}

.info-box {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
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
  width: 140px;
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

.table-badge {
  background: #f3f4f6;
  color: #374151;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-family: 'Courier New', monospace;
}

.type-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.type-badge.sql {
  background: #dbeafe;
  color: #1e40af;
}

.type-badge.json {
  background: #fef3c7;
  color: #92400e;
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.description-text {
  color: #6b7280;
  font-size: 13px;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.test-modal {
  max-width: 900px;
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  color: #111827;
}

.close-btn {
  background: none;
  border: none;
  font-size: 28px;
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
  cursor: pointer;
}

.sql-editor {
  font-family: 'Courier New', monospace;
  background: #f9fafb;
  resize: vertical;
}

.editor-toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.toolbar-label {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
}

.placeholder-btn {
  background: #eff6ff;
  color: #1e40af;
  border: 1px solid #bfdbfe;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-family: 'Courier New', monospace;
  cursor: pointer;
  transition: all 0.2s;
}

.placeholder-btn:hover {
  background: #dbeafe;
  border-color: #93c5fd;
}

.help-text {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #6b7280;
}

.help-text code {
  background: #f3f4f6;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 11px;
}

.placeholder-help {
  margin-top: 16px;
}

.placeholder-help details {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
}

.placeholder-help summary {
  cursor: pointer;
  font-weight: 500;
  color: #374151;
  user-select: none;
}

.placeholder-list {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.placeholder-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px;
  background: white;
  border-radius: 4px;
}

.placeholder-item code {
  color: #1e40af;
  font-weight: 600;
}

.placeholder-item span {
  font-size: 13px;
  color: #374151;
}

.placeholder-item small {
  font-size: 12px;
  color: #6b7280;
  font-family: 'Courier New', monospace;
}

/* Test Result Styles */
.test-result {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.result-section {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.result-section.success {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.result-section h3 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.result-section pre {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  padding: 12px;
  margin: 0;
  overflow-x: auto;
  font-size: 12px;
  font-family: 'Courier New', monospace;
}

.sql-output {
  color: #059669;
  font-weight: 500;
}
</style>
