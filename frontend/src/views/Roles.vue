<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import { getPermissionSets } from '../api/permissions'

const roles = ref([])
const permissionSets = ref([])
const loading = ref(false)
const error = ref(null)

// Modal states
const showAddModal = ref(false)
const showEditModal = ref(false)
const submitting = ref(false)
const currentRole = ref(null)

// Form data
const formData = ref({
  name: '',
  permission_set_id: null
})

// Fetch roles
const fetchRoles = async () => {
  loading.value = true
  error.value = null

  try {
    const res = await api.get('/roles')
    roles.value = res.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Roller yüklenemedi'
    console.error('Roller yükleme hatası:', e)
  } finally {
    loading.value = false
  }
}

// Fetch permission sets
const fetchPermissionSets = async () => {
  try {
    const res = await getPermissionSets()
    permissionSets.value = res.data.data ?? res.data
  } catch (e) {
    console.error('Yetki setleri yüklenemedi:', e)
  }
}

// Open add modal
const openAddModal = () => {
  formData.value = { name: '', permission_set_id: null }
  currentRole.value = null
  showAddModal.value = true
}

// Open edit modal
const openEditModal = (role) => {
  currentRole.value = role
  formData.value = {
    name: role.name,
    permission_set_id: role.permission_set_id
  }
  showEditModal.value = true
}

// Submit form
const handleSubmit = async () => {
  if (!formData.value.name.trim()) {
    alert('Lütfen rol adı girin')
    return
  }

  submitting.value = true

  try {
    const data = {
      name: formData.value.name,
      permission_set_id: formData.value.permission_set_id || null
    }

    if (currentRole.value) {
      await api.put(`/roles/${currentRole.value.id}`, data)
    } else {
      await api.post('/roles', data)
    }

    await fetchRoles()
    showAddModal.value = false
    showEditModal.value = false
    currentRole.value = null
  } catch (e) {
    alert(e.response?.data?.message || 'Rol kaydedilirken bir hata oluştu')
  } finally {
    submitting.value = false
  }
}

// Delete role
const deleteRole = async (role) => {
  if (!confirm(`"${role.name}" rolünü silmek istediğinize emin misiniz?`)) {
    return
  }

  try {
    await api.delete(`/roles/${role.id}`)
    await fetchRoles()
  } catch (e) {
    alert(e.response?.data?.message || 'Rol silinirken bir hata oluştu')
  }
}

// Get permission set name by id
const getPermissionSetName = (permissionSetId) => {
  if (!permissionSetId) return 'Yetki seti atanmamış'
  const permSet = permissionSets.value.find(ps => ps.id === permissionSetId)
  return permSet ? permSet.name : 'Bilinmeyen yetki seti'
}

onMounted(async () => {
  await Promise.all([fetchRoles(), fetchPermissionSets()])
})
</script>

<template>
  <div class="roles-page">
    <div class="header">
      <div>
        <h1>Roller</h1>
        <p class="description">Rolleri yönetin ve yetki setleri atayın</p>
      </div>
      <button class="btn-primary" @click="openAddModal">+ Yeni Rol</button>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      Yükleniyor...
    </div>

    <div v-else-if="error" class="error-box">
      ❌ {{ error }}
    </div>

    <div v-else-if="roles.length === 0" class="empty-state">
      <div class="empty-icon">👥</div>
      <p>Henüz rol yok</p>
      <button class="btn-primary" @click="openAddModal">İlk Rolü Oluştur</button>
    </div>

    <div v-else class="roles-grid">
      <div
        v-for="role in roles"
        :key="role.id"
        class="role-card"
      >
        <div class="card-header">
          <div class="role-info">
            <h3>{{ role.name }}</h3>
            <span class="role-id">ID: {{ role.id }}</span>
          </div>
          <div class="card-actions">
            <button class="btn-icon-small" @click="openEditModal(role)" title="Düzenle">
              ✏️
            </button>
            <button class="btn-icon-small btn-danger" @click="deleteRole(role)" title="Sil">
              🗑️
            </button>
          </div>
        </div>
        
        <div class="card-body">
          <div class="permission-set-info">
            <label>Yetki Seti:</label>
            <div v-if="role.permission_set" class="permission-set-badge">
              🔐 {{ role.permission_set.name }}
            </div>
            <div v-else class="no-permission-set">
              Yetki seti atanmamış
            </div>
          </div>

          <div v-if="role.permission_set?.permissions" class="permissions-summary">
            <div class="summary-label">Yetkili Tablolar:</div>
            <div class="tables-count">
              {{ Object.keys(role.permission_set.permissions).length }} tablo
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showAddModal || showEditModal" class="modal-overlay" @click.self="showAddModal = false; showEditModal = false">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ currentRole ? 'Rol Düzenle' : 'Yeni Rol' }}</h2>
          <button class="close-btn" @click="showAddModal = false; showEditModal = false" :disabled="submitting">×</button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label>Rol Adı <span class="required">*</span></label>
            <input
              v-model="formData.name"
              type="text"
              placeholder="Örn: Admin, Editör, Görüntüleyici"
              class="form-input"
              :disabled="submitting"
            />
          </div>

          <div class="form-group">
            <label>Yetki Seti</label>
            <select
              v-model="formData.permission_set_id"
              class="form-select"
              :disabled="submitting"
            >
              <option :value="null">Yetki seti seçin (opsiyonel)</option>
              <option
                v-for="permSet in permissionSets"
                :key="permSet.id"
                :value="permSet.id"
              >
                {{ permSet.name }}
              </option>
            </select>
            <p class="form-hint">Bu role atanacak yetki setini seçin</p>
          </div>

          <div v-if="formData.permission_set_id" class="selected-permission-preview">
            <div class="preview-header">Seçilen Yetki Seti Önizlemesi:</div>
            <div class="preview-content">
              <div v-if="permissionSets.find(ps => ps.id === formData.permission_set_id)">
                <strong>{{ permissionSets.find(ps => ps.id === formData.permission_set_id).name }}</strong>
                <div class="preview-tables">
                  {{ Object.keys(permissionSets.find(ps => ps.id === formData.permission_set_id).permissions || {}).length }} tablo yetkisi
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" @click="showAddModal = false; showEditModal = false" :disabled="submitting">
            İptal
          </button>
          <button type="button" class="btn-primary" @click="handleSubmit" :disabled="submitting || !formData.name.trim()">
            <span v-if="submitting" class="spinner"></span>
            {{ submitting ? 'Kaydediliyor...' : 'Kaydet' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.roles-page {
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
  width: 20px;
  height: 20px;
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

.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.role-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  overflow: hidden;
  transition: all 0.2s;
}

.role-card:hover {
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

.role-info h3 {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.role-id {
  font-size: 12px;
  color: #9ca3af;
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

.permission-set-info {
  margin-bottom: 16px;
}

.permission-set-info label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.permission-set-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.no-permission-set {
  color: #9ca3af;
  font-size: 14px;
  font-style: italic;
  padding: 8px 0;
}

.permissions-summary {
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.summary-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 6px;
}

.tables-count {
  font-size: 14px;
  color: #111827;
  font-weight: 500;
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
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.2s;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
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
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s;
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
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.required {
  color: #dc2626;
}

.form-input,
.form-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s;
  font-family: inherit;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled,
.form-select:disabled {
  background: #f9fafb;
  cursor: not-allowed;
}

.form-hint {
  margin-top: 6px;
  font-size: 12px;
  color: #6b7280;
}

.selected-permission-preview {
  margin-top: 20px;
  padding: 16px;
  background: #f0f9ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
}

.preview-header {
  font-size: 12px;
  font-weight: 600;
  color: #1e40af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.preview-content strong {
  display: block;
  color: #1e3a8a;
  margin-bottom: 4px;
}

.preview-tables {
  font-size: 13px;
  color: #3b82f6;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.btn-cancel {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
