// frontend/src/api/permissions.js
import api from './axios'

export function getPermissionSets() {
  return api.get('/admin/permissions')
}

export function getPermissionSet(id) {
  return api.get(`/admin/permissions/${id}`)
}

export function createPermissionSet(data) {
  return api.post('/admin/permissions', data)
}

export function updatePermissionSet(id, data) {
  return api.put(`/admin/permissions/${id}`, data)
}

export function deletePermissionSet(id) {
  return api.delete(`/admin/permissions/${id}`)
}

