// frontend/src/api/generic.js
import api from './axios'

/**
 * Generic CRUD operasyonları - Dinamik tablolar için
 */

// Listele (pagination destekli)
export function fetchRecords(tableName, params = {}) {
  return api.get(`/${tableName}`, { params })
}

// Tek kayıt getir
export function getRecord(tableName, id) {
  return api.get(`/${tableName}/${id}`)
}

// Yeni kayıt oluştur
export function createRecord(tableName, data) {
  return api.post(`/${tableName}`, data)
}

// Kayıt güncelle
export function updateRecord(tableName, id, data) {
  return api.put(`/${tableName}/${id}`, data)
}

// Kayıt sil
export function deleteRecord(tableName, id) {
  return api.delete(`/${tableName}/${id}`)
}