# 🎯 Filtre Atama - Hızlı Başlangıç Kılavuzu

## ✅ Şu Anda Çalışan Sistem

Backend tamamen hazır! Filtreleri **API üzerinden** atayabilirsiniz.

---

## 🚀 Adım Adım Filtre Atama

### Adım 1: Filtre Oluştur (UI'da)
1. **Filters** sayfasına git
2. **"Yeni Filtre"** tıkla
3. Bilgileri doldur:
   - **Ad**: Sadece Kendi Kayıtları
   - **Tablo**: users
   - **SQL**: `created_by = {user_id}`
4. **Kaydet**
5. **Filtre ID'sini not al** (örn: 1)

### Adım 2: Permission Set ID'sini Bul
1. **Yetkiler** sayfasına git
2. Düzenlemek istediğin permission set'i bul
3. **Browser Console'u aç** (F12)
4. Şunu çalıştır:
```javascript
// Permission set'leri görmek için
fetch('/api/admin/permissions', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token')
  }
}).then(r => r.json()).then(d => console.table(d.data))
```
5. **Permission Set ID'sini not al** (örn: 2)

### Adım 3: Filtreyi Ata (Browser Console'da)
```javascript
// Filtreyi permission set'e ata
fetch('/api/permission-sets/2/filters/attach', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token'),
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    filter_id: 1,
    table_name: 'users',
    action: 'list',
    priority: 10
  })
}).then(r => r.json()).then(d => console.log(d))
```

### Adım 4: Test Et!
1. O permission set'e sahip bir role ile giriş yap
2. **Users** sayfasına git
3. 🎉 Artık sadece kendi kayıtlarını görüyorsun!

---

## 📡 API Komutları (Postman/cURL için)

### Filtre Ata
```bash
POST /api/permission-sets/{permissionSetId}/filters/attach

Body:
{
  "filter_id": 1,
  "table_name": "users",
  "action": "list",
  "priority": 10
}
```

### Atanmış Filtreleri Gör
```bash
GET /api/permission-sets/{permissionSetId}/filters
```

### Filtre Çıkar
```bash
POST /api/permission-sets/{permissionSetId}/filters/detach

Body:
{
  "filter_id": 1,
  "table_name": "users",
  "action": "list"
}
```

---

## 🎨 Frontend UI (Gelecek Geliştirme)

Permissions.vue sayfasına filtre UI'ı eklemek için:

### Basit Versiyon:
Tablo modal'ına her action için filtre dropdown'ı ekle:

```vue
<!-- Her action için filtre seçimi -->
<div class="filter-section">
  <label>Filtreler (List için)</label>
  <select multiple v-model="selectedFilters.list">
    <option 
      v-for="filter in availableFilters.filter(f => f.table_name === currentTable.name)" 
      :key="filter.id" 
      :value="filter.id"
    >
      {{ filter.name }}
    </option>
  </select>
</div>
```

### Gelişmiş Versiyon:
- Drag & drop filtre sıralaması
- Filtre önizleme
- Toplu filtre atama

---

## 🧪 Test Senaryosu

### Senaryo: Sales Rolü Sadece Kendi Kayıtlarını Görsün

**1. Filtre Oluştur:**
- Ad: "Sadece Kendi Kayıtları"
- Tablo: users
- SQL: `created_by = {user_id}`
- ID: 1

**2. Permission Set Bul:**
- "Sales Permissions" → ID: 2

**3. Filtreyi Ata (Console):**
```javascript
fetch('/api/permission-sets/2/filters/attach', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token'),
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    filter_id: 1,
    table_name: 'users',
    action: 'list',
    priority: 10
  })
}).then(r => r.json()).then(console.log)
```

**4. Test:**
- Sales role'üne sahip kullanıcı ile giriş yap
- Users sayfasına git
- ✅ Sadece kendi oluşturduğun kullanıcıları görüyorsun!

---

## 💡 İpuçları

### Birden Fazla Filtre
Aynı tablo ve action için birden fazla filtre atayabilirsiniz:
```javascript
// Filtre 1: Sadece kendi kayıtları
filter_id: 1, priority: 10

// Filtre 2: Sadece aktif kayıtlar
filter_id: 2, priority: 5
```

Filtreler **AND** ile birleştirilir:
```sql
WHERE (created_by = 1) AND (status = 'active')
```

### Priority (Öncelik)
- Yüksek priority önce uygulanır
- 0-100 arası değer
- Varsayılan: 0

---

## 🔧 Troubleshooting

### Filtre çalışmıyor?
1. **Filtrenin aktif olduğunu** kontrol et (Filters sayfasında)
2. **Doğru permission set'e atandığını** kontrol et
3. **Kullanıcının doğru role sahip** olduğunu kontrol et
4. **Role'ün doğru permission set'e sahip** olduğunu kontrol et

### Console'da kontrol:
```javascript
// Atanmış filtreleri gör
fetch('/api/permission-sets/2/filters', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token')
  }
}).then(r => r.json()).then(d => console.table(d))
```

---

## ✅ Sistem Akışı

```
1. User giriş yapar
   ↓
2. User'ın Role'ü bulunur
   ↓
3. Role'ün Permission Set'i bulunur
   ↓
4. Permission Set'in Filters'ları bulunur
   ↓
5. Filters SQL'e çevrilir
   ↓
6. Query'ye uygulanır (WHERE clause)
   ↓
7. Sadece yetkili kayıtlar gösterilir
```

---

## 🎯 Sonuç

**Sistem şu anda tamamen çalışıyor!**

- ✅ Backend hazır
- ✅ API çalışıyor
- ✅ Row-level security aktif
- ⏳ Frontend UI (opsiyonel, manuel atama mümkün)

**Şimdilik browser console ile filtre atayabilirsiniz.**
**İlerleyen zamanlarda Permissions.vue'ya UI ekleyebiliriz.**
