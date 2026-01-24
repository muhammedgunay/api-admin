<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import { getPermissionSets, createPermissionSet, updatePermissionSet, deletePermissionSet } from '../api/permissions'

const permissionSets = ref([])
const tables = ref([])
const loading = ref(false)
const error = ref(null)

// Modal state'leri
const showAddModal = ref(false)
const showEditModal = ref(false)
const showTableModal = ref(false)
const showFilterModal = ref(false) // ← Yeni: Filtre modal'ı
const submitting = ref(false)
const currentPermissionSet = ref(null)
const currentTable = ref(null)

// Form state
const formData = ref({
  name: '',
  permissions: {}
})

// Tablo ve kolon seçimleri
const tablePermissions = ref({})

// Filtre yönetimi
const availableFilters = ref([]) // Tüm filtreler
const assignedFilters = ref([]) // Permission set'e atanmış filtreler
const selectedFilters = ref({}) // Tablo modal'ında seçilen filtreler: { list: [filterId], create: [], ... }

// Tabloları ve kolonları çek
const fetchTables = async () => {
  try {
    const [tablesRes, dynamicTablesRes] = await Promise.allSettled([
      api.get('/tables'),
      api.get('/dynamic-tables'),
    ])

    const allTables = []

    if (tablesRes.status === 'fulfilled') {
      const tableData = tablesRes.value.data.data ?? tablesRes.value.data
      if (Array.isArray(tableData)) {
        allTables.push(...tableData.map(t => ({
          ...t,
          display_name: t.display_name || t.name,
          columns: t.columns || []
        })))
      }
    }

    if (dynamicTablesRes.status === 'fulfilled') {
      const dynamicTableData = dynamicTablesRes.value.data.data ?? dynamicTablesRes.value.data
      if (Array.isArray(dynamicTableData)) {
        allTables.push(...dynamicTableData.map(t => ({
          ...t,
          display_name: t.label || t.name,
          columns: t.columns || []
        })))
      }
    }

    tables.value = allTables
  } catch (e) {
    console.error('Tablolar yüklenemedi:', e)
  }
}

// Permission set'leri çek
const fetchPermissionSets = async () => {
  loading.value = true
  error.value = null

  try {
    const res = await getPermissionSets()
    permissionSets.value = res.data.data ?? res.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Yetki setleri yüklenemedi'
    console.error('Yetki setleri yükleme hatası:', e)
  } finally {
    loading.value = false
  }
}

// Filtreleri çek
const fetchFilters = async () => {
  try {
    const res = await api.get('/filters?paginate=false')
    availableFilters.value = res.data
  } catch (e) {
    console.error('Filtreler yüklenemedi:', e)
  }
}

// Permission set'e atanmış filtreleri çek
const fetchAssignedFilters = async (permissionSetId) => {
  try {
    const res = await api.get(`/permission-sets/${permissionSetId}/filters`)
    assignedFilters.value = res.data
  } catch (e) {
    console.error('Atanmış filtreler yüklenemedi:', e)
    assignedFilters.value = []
  }
}

// Yeni permission set oluştur
const openAddModal = () => {
  formData.value = { name: '', permissions: {} }
  tablePermissions.value = {}
  showAddModal.value = true
}

// Permission set düzenle
const openEditModal = async (permissionSet) => {
  currentPermissionSet.value = permissionSet
  formData.value = {
    name: permissionSet.name,
    permissions: permissionSet.permissions || {}
  }
  
  // Mevcut yetkileri tablePermissions'a yükle
  tablePermissions.value = {}
  Object.keys(formData.value.permissions).forEach(tableName => {
    tablePermissions.value[tableName] = { ...formData.value.permissions[tableName] }
  })
  
  // Atanmış filtreleri yükle
  await fetchAssignedFilters(permissionSet.id)
  
  showEditModal.value = true
}

// Tablo yetkilerini düzenle
const openTableModal = (table) => {
  currentTable.value = table
  
  // Mevcut yetkileri yükle
  const tableName = table.name
  if (tablePermissions.value[tableName]) {
    // Zaten var
  } else {
    // Yeni oluştur
    tablePermissions.value[tableName] = {
      list: { columns: [] },
      view: { columns: [] },
      create: { columns: [] },
      update: { columns: [] },
      delete: false
    }
  }
  
  // Filtre seçimlerini başlat
  selectedFilters.value = {
    list: [],
    view: [],
    create: [],
    update: [],
    delete: []
  }
  
  // Eğer permission set düzenleme modundaysa, atanmış filtreleri yükle
  if (currentPermissionSet.value) {
    const tableFilters = assignedFilters.value.filter(f => f.pivot.table_name === tableName)
    tableFilters.forEach(filter => {
      const action = filter.pivot.action
      if (selectedFilters.value[action]) {
        selectedFilters.value[action].push(filter.id)
      }
    })
  }
  
  showTableModal.value = true
}

// Tablo yetkilerini kaydet
const saveTablePermissions = async () => {
  if (!currentTable.value) return
  
  const tableName = currentTable.value.name
  const permissions = tablePermissions.value[tableName]
  
  // Boş kolonları temizle
  Object.keys(permissions).forEach(action => {
    if (action !== 'delete' && permissions[action].columns) {
      permissions[action].columns = permissions[action].columns.filter(c => c)
    }
  })
  
  formData.value.permissions[tableName] = permissions
  
  // Eğer permission set düzenleme modundaysa, filtreleri de kaydet
  if (currentPermissionSet.value && selectedFilters.value) {
    try {
      // Her action için filtreleri kaydet
      const actions = ['list', 'create', 'update', 'delete']
      
      for (const action of actions) {
        const selectedFilterIds = selectedFilters.value[action] || []
        const existingFilters = assignedFilters.value.filter(
          f => f.pivot.table_name === tableName && f.pivot.action === action
        )
        
        // Yeni eklenen filtreleri ata
        for (const filterId of selectedFilterIds) {
          const alreadyAssigned = existingFilters.some(f => f.id === filterId)
          if (!alreadyAssigned) {
            await api.post(`/permission-sets/${currentPermissionSet.value.id}/filters/attach`, {
              filter_id: filterId,
              table_name: tableName,
              action: action,
              priority: 10
            })
          }
        }
        
        // Kaldırılan filtreleri çıkar
        for (const filter of existingFilters) {
          if (!selectedFilterIds.includes(filter.id)) {
            await api.post(`/permission-sets/${currentPermissionSet.value.id}/filters/detach`, {
              filter_id: filter.id,
              table_name: tableName,
              action: action
            })
          }
        }
      }
      
      // Atanmış filtreleri yeniden yükle
      await fetchAssignedFilters(currentPermissionSet.value.id)
    } catch (e) {
      console.error('Filtre kaydetme hatası:', e)
      alert('Filtreler kaydedilirken bir hata oluştu')
    }
  }
  
  showTableModal.value = false
}


// Kolon seçimi
const toggleColumn = (action, columnName) => {
  if (!currentTable.value) return
  const tableName = currentTable.value.name
  if (!tablePermissions.value[tableName] || !tablePermissions.value[tableName][action]) {
    if (!tablePermissions.value[tableName]) {
      tablePermissions.value[tableName] = {}
    }
    tablePermissions.value[tableName][action] = { columns: [] }
  }
  if (!tablePermissions.value[tableName][action].columns) {
    tablePermissions.value[tableName][action].columns = []
  }
  
  const index = tablePermissions.value[tableName][action].columns.indexOf(columnName)
  if (index > -1) {
    tablePermissions.value[tableName][action].columns.splice(index, 1)
  } else {
    tablePermissions.value[tableName][action].columns.push(columnName)
  }
}

// Tüm kolonları seç/kaldır
const toggleAllColumns = (action) => {
  if (!currentTable.value || !currentTable.value.columns) return
  const tableName = currentTable.value.name
  const allColumns = currentTable.value.columns.map(c => c.name)
  
  if (!tablePermissions.value[tableName] || !tablePermissions.value[tableName][action]) {
    if (!tablePermissions.value[tableName]) {
      tablePermissions.value[tableName] = {}
    }
    tablePermissions.value[tableName][action] = { columns: [] }
  }
  
  const currentColumns = tablePermissions.value[tableName][action].columns || []
  
  if (currentColumns.length === allColumns.length) {
    tablePermissions.value[tableName][action].columns = []
  } else {
    tablePermissions.value[tableName][action].columns = [...allColumns]
  }
}

// Kolon seçili mi?
const isColumnSelected = (action, columnName) => {
  if (!currentTable.value) return false
  const tableName = currentTable.value.name
  return tablePermissions.value[tableName]?.[action]?.columns?.includes(columnName) || false
}

// Filtre seç/kaldır
const toggleFilter = (action, filterId, checked) => {
  if (!selectedFilters.value[action]) {
    selectedFilters.value[action] = []
  }
  
  if (checked) {
    if (!selectedFilters.value[action].includes(filterId)) {
      selectedFilters.value[action].push(filterId)
    }
  } else {
    const index = selectedFilters.value[action].indexOf(filterId)
    if (index > -1) {
      selectedFilters.value[action].splice(index, 1)
    }
  }
}

// Form gönder
const handleSubmit = async () => {
  submitting.value = true
  
  try {
    // Boş tabloları temizle
    const cleanedPermissions = {}
    Object.keys(formData.value.permissions).forEach(tableName => {
      const perms = formData.value.permissions[tableName]
      const hasAnyPermission = Object.keys(perms).some(action => {
        if (action === 'delete') return perms[action] === true
        return perms[action]?.columns?.length > 0
      })
      
      if (hasAnyPermission) {
        cleanedPermissions[tableName] = perms
      }
    })
    
    const data = {
      name: formData.value.name,
      permissions: cleanedPermissions
    }
    
    if (currentPermissionSet.value) {
      await updatePermissionSet(currentPermissionSet.value.id, data)
    } else {
      await createPermissionSet(data)
    }
    
    await fetchPermissionSets()
    showAddModal.value = false
    showEditModal.value = false
    currentPermissionSet.value = null
  } catch (e) {
    alert(e.response?.data?.message || 'Yetki seti kaydedilirken bir hata oluştu')
  } finally {
    submitting.value = false
  }
}

// Permission set sil
const deleteSet = async (permissionSet) => {
  if (!confirm(`"${permissionSet.name}" yetki setini silmek istediğinize emin misiniz?`)) {
    return
  }
  
  try {
    await deletePermissionSet(permissionSet.id)
    await fetchPermissionSets()
  } catch (e) {
    alert(e.response?.data?.message || 'Yetki seti silinirken bir hata oluştu')
  }
}

// Tablo için yetki var mı?
const hasTablePermission = (tableName) => {
  return formData.value.permissions[tableName] && 
         Object.keys(formData.value.permissions[tableName]).some(action => {
           if (action === 'delete') return formData.value.permissions[tableName][action] === true
           return formData.value.permissions[tableName][action]?.columns?.length > 0
         })
}

onMounted(async () => {
  await Promise.all([fetchTables(), fetchPermissionSets(), fetchFilters()])
})
</script>

<template>
  <div class="permissions-page">
    <div class="header">
      <div>
        <h1>Yetkiler</h1>
        <p class="description">Yetki setlerini oluşturun ve yönetin</p>
      </div>
      <button class="btn-primary" @click="openAddModal">+ Yeni Yetki Seti</button>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      Yükleniyor...
    </div>

    <div v-else-if="error" class="error-box">
      ❌ {{ error }}
    </div>

    <div v-else-if="permissionSets.length === 0" class="empty-state">
      <div class="empty-icon">🔐</div>
      <p>Henüz yetki seti yok</p>
      <button class="btn-primary" @click="openAddModal">İlk Yetki Setini Oluştur</button>
    </div>

    <div v-else class="permission-sets-grid">
      <div
        v-for="permissionSet in permissionSets"
        :key="permissionSet.id"
        class="permission-set-card"
      >
        <div class="card-header">
          <h3>{{ permissionSet.name }}</h3>
          <div class="card-actions">
            <button class="btn-icon-small" @click="openEditModal(permissionSet)" title="Düzenle">
              ✏️
            </button>
            <button class="btn-icon-small btn-danger" @click="deleteSet(permissionSet)" title="Sil">
              🗑️
            </button>
          </div>
        </div>
        
        <div class="card-body">
          <div v-if="permissionSet.permissions && Object.keys(permissionSet.permissions).length > 0" class="tables-list">
            <div
              v-for="(perms, tableName) in permissionSet.permissions"
              :key="tableName"
              class="table-item"
            >
              <strong>{{ tableName }}</strong>
              <div class="actions-list">
                <span v-if="perms.list?.columns?.length" class="action-badge">
                  List ({{ perms.list.columns.length }} kolon)
                </span>
                <span v-if="perms.view?.columns?.length" class="action-badge">
                  View ({{ perms.view.columns.length }} kolon)
                </span>
                <span v-if="perms.create?.columns?.length" class="action-badge">
                  Create ({{ perms.create.columns.length }} kolon)
                </span>
                <span v-if="perms.update?.columns?.length" class="action-badge">
                  Update ({{ perms.update.columns.length }} kolon)
                </span>
                <span v-if="perms.delete" class="action-badge action-badge-danger">
                  Delete
                </span>
              </div>
            </div>
          </div>
          <div v-else class="no-permissions">
            Henüz yetki tanımlanmamış
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal || showEditModal" class="modal-overlay" @click.self="showAddModal = false; showEditModal = false">
      <div class="modal modal-large">
        <div class="modal-header">
          <h2>{{ currentPermissionSet ? 'Yetki Seti Düzenle' : 'Yeni Yetki Seti' }}</h2>
          <button class="close-btn" @click="showAddModal = false; showEditModal = false" :disabled="submitting">×</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label>Yetki Seti Adı <span class="required">*</span></label>
            <input
              v-model="formData.name"
              type="text"
              placeholder="Örn: Admin Yetkileri"
              class="form-input"
            />
          </div>

          <div class="tables-section">
            <div class="section-header">
              <h3>Tablolar ve Kolonlar</h3>
              <p class="section-description">Her tablo için işlem yetkilerini ve kolon erişimlerini belirleyin</p>
            </div>

            <div v-if="tables.length === 0" class="no-tables">
              Henüz tablo yok
            </div>

            <div v-else class="tables-list-modal">
              <div
                v-for="table in tables"
                :key="table.id || table.name"
                class="table-item-modal"
                :class="{ 'has-permission': hasTablePermission(table.name) }"
              >
                <div class="table-item-header">
                  <div>
                    <strong>{{ table.display_name || table.name }}</strong>
                    <span class="table-name">{{ table.name }}</span>
                  </div>
                  <button class="btn-secondary" @click="openTableModal(table)">
                    {{ hasTablePermission(table.name) ? 'Düzenle' : 'Yetki Ver' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" @click="showAddModal = false; showEditModal = false" :disabled="submitting">
            İptal
          </button>
          <button type="button" class="btn-primary" @click="handleSubmit" :disabled="submitting || !formData.name">
            <span v-if="submitting" class="spinner"></span>
            {{ submitting ? 'Kaydediliyor...' : 'Kaydet' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Table Permissions Modal -->
    <div v-if="showTableModal && currentTable" class="modal-overlay" @click.self="showTableModal = false">
      <div class="modal modal-large">
        <div class="modal-header">
          <h2>{{ currentTable.display_name || currentTable.name }} - Yetki Ayarları</h2>
          <button class="close-btn" @click="showTableModal = false">×</button>
        </div>

        <div class="modal-body">
          <div class="actions-section">
            <!-- List -->
            <div class="action-group">
              <div class="action-header">
                <label class="action-checkbox">
                  <input
                    type="checkbox"
                    :checked="tablePermissions[currentTable?.name]?.list?.columns?.length > 0"
                    @change="toggleAllColumns('list')"
                  />
                  <span>List (Görüntüleme)</span>
                </label>
              </div>
              <div v-if="tablePermissions[currentTable?.name]?.list?.columns?.length > 0 && currentTable?.columns" class="columns-list">
                <div
                  v-for="column in currentTable.columns"
                  :key="column.id || column.name"
                  class="column-item"
                >
                  <label class="column-checkbox">
                    <input
                      type="checkbox"
                      :checked="isColumnSelected('list', column.name)"
                      @change="toggleColumn('list', column.name)"
                    />
                    <span>{{ column.display_name || column.label || column.name }}</span>
                    <span class="column-type">{{ column.type }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- View -->
            <div class="action-group">
              <div class="action-header">
                <label class="action-checkbox">
                  <input
                    type="checkbox"
                    :checked="tablePermissions[currentTable?.name]?.view?.columns?.length > 0"
                    @change="toggleAllColumns('view')"
                  />
                  <span>View (Detay Görüntüleme)</span>
                </label>
              </div>
              <div v-if="tablePermissions[currentTable?.name]?.view?.columns?.length > 0 && currentTable?.columns" class="columns-list">
                <div
                  v-for="column in currentTable.columns"
                  :key="column.id || column.name"
                  class="column-item"
                >
                  <label class="column-checkbox">
                    <input
                      type="checkbox"
                      :checked="isColumnSelected('view', column.name)"
                      @change="toggleColumn('view', column.name)"
                    />
                    <span>{{ column.display_name || column.label || column.name }}</span>
                    <span class="column-type">{{ column.type }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Create -->
            <div class="action-group">
              <div class="action-header">
                <label class="action-checkbox">
                  <input
                    type="checkbox"
                    :checked="tablePermissions[currentTable?.name]?.create?.columns?.length > 0"
                    @change="toggleAllColumns('create')"
                  />
                  <span>Create (Oluşturma)</span>
                </label>
              </div>
              <div v-if="tablePermissions[currentTable?.name]?.create?.columns?.length > 0 && currentTable?.columns" class="columns-list">
                <div
                  v-for="column in currentTable.columns"
                  :key="column.id || column.name"
                  class="column-item"
                >
                  <label class="column-checkbox">
                    <input
                      type="checkbox"
                      :checked="isColumnSelected('create', column.name)"
                      @change="toggleColumn('create', column.name)"
                    />
                    <span>{{ column.display_name || column.label || column.name }}</span>
                    <span class="column-type">{{ column.type }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Update -->
            <div class="action-group">
              <div class="action-header">
                <label class="action-checkbox">
                  <input
                    type="checkbox"
                    :checked="tablePermissions[currentTable?.name]?.update?.columns?.length > 0"
                    @change="toggleAllColumns('update')"
                  />
                  <span>Update (Güncelleme)</span>
                </label>
              </div>
              <div v-if="tablePermissions[currentTable?.name]?.update?.columns?.length > 0 && currentTable?.columns" class="columns-list">
                <div
                  v-for="column in currentTable.columns"
                  :key="column.id || column.name"
                  class="column-item"
                >
                  <label class="column-checkbox">
                    <input
                      type="checkbox"
                      :checked="isColumnSelected('update', column.name)"
                      @change="toggleColumn('update', column.name)"
                    />
                    <span>{{ column.display_name || column.label || column.name }}</span>
                    <span class="column-type">{{ column.type }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Delete -->
            <div class="action-group">
              <div class="action-header">
                <label class="action-checkbox">
                  <input
                    type="checkbox"
                    :checked="currentTable && tablePermissions[currentTable.name]?.delete === true"
                    @change="(e) => {
                      if (currentTable) {
                        if (!tablePermissions[currentTable.name]) {
                          tablePermissions[currentTable.name] = {}
                        }
                        tablePermissions[currentTable.name].delete = e.target.checked
                      }
                    }"
                  />
                  <span>Delete (Silme)</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" @click="showTableModal = false">
            İptal
          </button>
          <button type="button" class="btn-primary" @click="saveTablePermissions">
            Kaydet
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.permissions-page {
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

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.permission-sets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.permission-set-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  overflow: hidden;
  transition: all 0.2s;
}

.permission-set-card:hover {
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  transform: translateY(-2px);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.card-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.card-actions {
  display: flex;
  gap: 4px;
}

.btn-icon-small {
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 16px;
  padding: 6px 8px;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-icon-small:hover {
  background: #f3f4f6;
}

.btn-icon-small.btn-danger:hover {
  background: #fef2f2;
  color: #dc2626;
}

.card-body {
  padding: 20px;
}

.tables-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.table-item {
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}

.table-item strong {
  display: block;
  margin-bottom: 8px;
  color: #111827;
  font-size: 14px;
}

.actions-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.action-badge {
  display: inline-block;
  background: #dbeafe;
  color: #1e40af;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.action-badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.no-permissions {
  color: #9ca3af;
  font-size: 14px;
  font-style: italic;
  text-align: center;
  padding: 20px;
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
  animation: fadeIn 0.2s;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  animation: slideUp 0.2s;
}

.modal-large {
  max-width: 900px;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #111827;
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 28px;
  color: #6b7280;
  cursor: pointer;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
  line-height: 1;
  padding: 0;
}

.close-btn:hover:not(:disabled) {
  background: #f3f4f6;
  color: #111827;
}

.close-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.required {
  color: #ef4444;
  margin-left: 2px;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  color: #111827;
  transition: all 0.2s;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.tables-section {
  margin-top: 24px;
}

.section-header {
  margin-bottom: 16px;
}

.section-header h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
}

.section-description {
  margin: 0;
  color: #6b7280;
  font-size: 13px;
}

.tables-list-modal {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 400px;
  overflow-y: auto;
}

.table-item-modal {
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.2s;
}

.table-item-modal.has-permission {
  border-color: #3b82f6;
  background: #eff6ff;
}

.table-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.table-item-header strong {
  display: block;
  color: #111827;
  font-size: 14px;
  margin-bottom: 4px;
}

.table-name {
  font-size: 12px;
  color: #6b7280;
  font-family: 'Courier New', monospace;
  background: #f3f4f6;
  padding: 2px 6px;
  border-radius: 4px;
}

.btn-secondary {
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

.btn-secondary:hover {
  background: #e5e7eb;
  color: #111827;
}

.no-tables {
  text-align: center;
  padding: 40px;
  color: #9ca3af;
  font-size: 14px;
}

.actions-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.action-group {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.action-header {
  margin-bottom: 12px;
}

.action-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: 500;
  color: #111827;
}

.action-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.columns-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
}

.column-item {
  padding: 8px;
  background: #f9fafb;
  border-radius: 6px;
}

.column-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 13px;
}

.column-checkbox input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.column-checkbox span:first-of-type {
  flex: 1;
  color: #374151;
}

.column-type {
  font-size: 11px;
  color: #9ca3af;
  background: #e5e7eb;
  padding: 2px 6px;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel {
  padding: 10px 20px;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
