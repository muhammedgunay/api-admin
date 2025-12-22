// frontend/src/api/columns.js
import api from './axios'

// Tüm kolonları getir
export function getAllColumns() {
  return api.get('/columns')
}

// Belirli bir tablonun kolonlarını getir
export function getColumns(tableId) {
  return api.get(`/tables/${tableId}/columns`)
}

// Yeni kolon ekle
export function createColumn(tableId, data) {
  return api.post(`/tables/${tableId}/columns`, data)
}

// Kolonu güncelle
export function updateColumn(tableId, columnId, data) {
  return api.put(`/tables/${tableId}/columns/${columnId}`, data)
}

// Kolonu sil
export function deleteColumn(tableId, columnId) {
  return api.delete(`/tables/${tableId}/columns/${columnId}`)
}

// Tüm kolonları güncelle (sıralama için)
export function updateColumnsOrder(tableId, columns) {
  return api.put(`/tables/${tableId}/columns/reorder`, { columns })
}