<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    required: true,
  },
  fields: {
    type: Array,
    required: true,
    // Örnek: [{ name: 'name', label: 'Ad', type: 'text', required: true, value: '' }]
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'submit'])

const formData = ref({})
const errors = ref({})

// Form data'yı fields'a göre başlat
watch(() => props.fields, (newFields) => {
  const data = { ...formData.value } // Mevcut değerleri koru
  newFields.forEach(field => {
    // Sadece henüz değeri olmayanları ekle
    if (!(field.name in data)) {
      data[field.name] = field.value || ''
    }
  })
  formData.value = data
}, { immediate: true })

// Modal açıldığında formu sıfırla
watch(() => props.show, (isShow) => {
  if (isShow) {
    errors.value = {}
    const data = {}
    props.fields.forEach(field => {
      data[field.name] = field.value || ''
    })
    formData.value = data
  }
})

const close = () => {
  errors.value = {}
  emit('close')
}

const submit = () => {
  errors.value = {}
  
  // Validasyon
  let isValid = true
  props.fields.forEach(field => {
    if (field.required && !formData.value[field.name]) {
      errors.value[field.name] = `${field.label} zorunludur`
      isValid = false
    }
  })
  
  if (isValid) {
    emit('submit', { ...formData.value })
  }
}
</script>

<template>
  <div v-if="show" class="modal-overlay" @click.self="close">
    <div class="modal">
      <div class="modal-header">
        <h2>{{ title }}</h2>
        <button class="close-btn" @click="close" :disabled="loading">×</button>
      </div>

      <div class="modal-body">
        <form @submit.prevent="submit">
          <div v-for="field in fields" :key="field.name" class="form-group">
            <label :for="field.name">
              {{ field.label }}
              <span v-if="field.required" class="required">*</span>
            </label>
            
            <input
              v-if="['text', 'email', 'number', 'date', 'datetime-local', 'time', 'password'].includes(field.type) || !field.type"
              :id="field.name"
              v-model="formData[field.name]"
              :type="field.type || 'text'"
              :placeholder="field.placeholder || ''"
              class="form-input"
              :class="{ error: errors[field.name] }"
            />
            
            <textarea
              v-else-if="field.type === 'textarea'"
              :id="field.name"
              v-model="formData[field.name]"
              :placeholder="field.placeholder || ''"
              class="form-textarea"
              :class="{ error: errors[field.name] }"
              rows="4"
            />
            
            <select
              v-else-if="field.type === 'select'"
              :id="field.name"
              v-model="formData[field.name]"
              class="form-select"
              :class="{ error: errors[field.name] }"
              @change="field.onChange && field.onChange(formData[field.name])"
            >
              <option value="">Seçiniz...</option>
              <option v-for="option in field.options" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            
            <span v-if="field.hint" class="field-hint">
              💡 {{ field.hint }}
            </span>
            
            <span v-if="errors[field.name]" class="error-message">
              {{ errors[field.name] }}
            </span>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-cancel" @click="close" :disabled="loading">
          İptal
        </button>
        <button type="button" class="btn-primary" @click="submit" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          {{ loading ? 'Kaydediliyor...' : 'Kaydet' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
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
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.2s;
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

.form-group:last-child {
  margin-bottom: 0;
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

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  color: #111827;
  transition: all 0.2s;
  font-family: inherit;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input.error,
.form-textarea.error,
.form-select.error {
  border-color: #ef4444;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.error-message {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #ef4444;
}

.field-hint {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #6b7280;
  font-style: italic;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel,
.btn-primary {
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-cancel {
  background: #f3f4f6;
  color: #374151;
}

.btn-cancel:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-primary:disabled,
.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
