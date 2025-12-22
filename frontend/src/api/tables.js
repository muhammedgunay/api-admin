// frontend/src/api/tables.js
import api from './axios'

export function getTables() {
  return api.get('/tables')
}

export function createTable(data) {
  return api.post('/tables', data)
}

export function updateTable(id, data) {
  return api.put(`/tables/${id}`, data)
}

export function deleteTable(id) {
  return api.delete(`/tables/${id}`)
}

export function fixTable(id) {
  return api.post(`/tables/${id}/fix`)
}