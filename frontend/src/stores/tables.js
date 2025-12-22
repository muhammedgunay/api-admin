import { defineStore } from 'pinia'
import api from '../api/axios'

export const useTablesStore = defineStore('tables', {
  state: () => ({
    tables: [],
    loading: false,
  }),

  actions: {
    async fetchTables() {
      this.loading = true

      try {
        console.log('🔄 Tablolar çekiliyor...')
        
        // Hem Table hem DynamicTable çek
        const [tablesResponse, dynamicTablesResponse] = await Promise.allSettled([
          api.get('/tables'),
          api.get('/dynamic-tables'),
        ])

        console.log('📊 Tables Response:', tablesResponse)
        console.log('📊 DynamicTables Response:', dynamicTablesResponse)

        const tables = []
        
        // Table modelinden gelen veriler
        if (tablesResponse.status === 'fulfilled') {
          const response = tablesResponse.value
          console.log('✅ Tables API başarılı, response.data:', response.data)
          const tableData = Array.isArray(response.data) ? response.data : (response.data?.data ?? [])
          console.log('📋 Table Data (işlenmiş):', tableData)
          if (Array.isArray(tableData) && tableData.length > 0) {
            tables.push(...tableData)
            console.log(`✅ ${tableData.length} adet Table eklendi`)
          } else {
            console.log('⚠️ Table verisi boş veya array değil')
          }
        } else {
          console.error('❌ Tables API hatası:', tablesResponse.reason)
          if (tablesResponse.reason?.response) {
            console.error('Response data:', tablesResponse.reason.response.data)
            console.error('Response status:', tablesResponse.reason.response.status)
          }
        }

        // DynamicTable modelinden gelen veriler (label -> display_name'e çevir)
        if (dynamicTablesResponse.status === 'fulfilled') {
          const response = dynamicTablesResponse.value
          console.log('✅ DynamicTables API başarılı, response.data:', response.data)
          const dynamicTableData = Array.isArray(response.data) ? response.data : (response.data?.data ?? [])
          console.log('📋 DynamicTable Data (işlenmiş):', dynamicTableData)
          if (Array.isArray(dynamicTableData) && dynamicTableData.length > 0) {
            const convertedDynamicTables = dynamicTableData.map(table => ({
              ...table,
              display_name: table.label || table.name, // label'ı display_name'e çevir
            }))
            tables.push(...convertedDynamicTables)
            console.log(`✅ ${dynamicTableData.length} adet DynamicTable eklendi`)
          } else {
            console.log('⚠️ DynamicTable verisi boş veya array değil')
          }
        } else {
          console.error('❌ DynamicTables API hatası:', dynamicTablesResponse.reason)
          if (dynamicTablesResponse.reason?.response) {
            console.error('Response data:', dynamicTablesResponse.reason.response.data)
            console.error('Response status:', dynamicTablesResponse.reason.response.status)
          }
        }

        console.log('📊 Toplam tablo sayısı:', tables.length)
        console.log('📊 Tablolar:', tables)
        this.tables = tables
      } catch (error) {
        console.error('Tablolar alınamadı', error)
        this.tables = []
      } finally {
        this.loading = false
      }
    },
  },
})
