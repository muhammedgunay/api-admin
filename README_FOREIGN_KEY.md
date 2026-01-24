# 🔗 Foreign Key Desteği Eklendi!

## ✅ Yapılan Değişiklikler

### 1. **Backend - Database**

#### Migration: Foreign Key Alanları
- ✅ `columns` tablosuna foreign key desteği eklendi (UI'da kullanılan tablo)
- ✅ `dynamic_columns` tablosuna da eklendi (gelecekte kullanılmak üzere)
- ✅ Yeni alanlar:
  - `is_foreign_key`: Boolean - Bu kolon foreign key mi?
  - `foreign_table`: String - Hangi tabloya referans veriyor?
  - `foreign_column`: String - Hangi kolona referans veriyor? (genelde 'id')
  - `foreign_display_column`: String - Dropdown'da hangi kolon gösterilsin?
  - `on_delete`: Enum - Silme davranışı (cascade, set null, restrict, no action)
  - `on_update`: Enum - Güncelleme davranışı (cascade, set null, restrict, no action)

#### Migration Dosyaları
- `2026_01_18_202000_add_foreign_key_support_to_dynamic_columns.php`
- `2026_01_18_203000_add_is_required_to_dynamic_columns.php`
- `2026_01_18_204000_add_foreign_key_support_to_columns.php` ⭐ (Ana tablo)

### 2. **Backend - API**

#### Yeni Controller: `ForeignKeyController`
- ✅ `GET /api/foreign-key-options/{table}?display_column={column}`
  - Belirtilen tablo için foreign key seçeneklerini getirir
  - Dropdown'da gösterilecek veriler
  
- ✅ `GET /api/foreign-key-relations`
  - Tüm foreign key ilişkilerini listeler

#### Route Güncellemeleri
- ✅ `routes/api.php` dosyasına foreign key route'ları eklendi

### 3. **Frontend - Vue.js**

#### `TableDetail.vue` Güncellemeleri
- ✅ **Foreign Key Dropdown Desteği**
  - Form açıldığında foreign key alanları otomatik olarak dropdown'a dönüşüyor
  - API'den ilgili tablonun verileri çekiliyor
  - Kullanıcı dostu label'lar gösteriliyor

- ✅ **Tablo Görünümü**
  - Foreign key değerleri ID yerine label olarak gösteriliyor
  - Örnek: `user_id: 1` yerine `Ahmet Yılmaz` görünüyor

- ✅ **Cache Mekanizması**
  - Foreign key verileri cache'leniyor
  - Performans optimizasyonu

### 4. **Dokümantasyon**

#### Oluşturulan Dosyalar
- ✅ `FOREIGN_KEY_GUIDE.md` - Detaylı kullanım kılavuzu
- ✅ `ForeignKeyExampleSeeder.php` - Örnek seeder

---

## 🚀 Nasıl Kullanılır?

### Örnek: Posts Tablosu (Yazılar)

1. **Tablo Oluştur**
```sql
CREATE TABLE posts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    user_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

2. **Foreign Key Constraint Ekle**
```sql
ALTER TABLE posts
ADD CONSTRAINT fk_posts_user_id
FOREIGN KEY (user_id) REFERENCES users(id)
ON DELETE CASCADE
ON UPDATE CASCADE;
```

3. **columns'a Kolon Ekle:**
```sql
INSERT INTO columns (
    table_id, name, display_name, type,
    is_visible, is_editable, is_required,
    is_foreign_key, foreign_table, foreign_column, foreign_display_column,
    on_delete, on_update
) VALUES (
    1, -- posts tablosunun ID'si
    'user_id',
    'Yazar',
    'bigint',
    true,
    true,
    true,
    true, -- Foreign key aktif
    'users',
    'id',
    'name',
    'cascade',
    'cascade'
);
```

4. **Frontend'de Kullan**
- `/posts` sayfasına git
- "Yeni Kayıt" butonuna tıkla
- **Yazar** alanı otomatik olarak dropdown olacak
- Tüm kullanıcılar listelenecek

---

## 🎯 Örnek Seeder Çalıştır

```bash
php artisan db:seed --class=ForeignKeyExampleSeeder
```

Bu seeder:
- ✅ `posts` tablosunu oluşturur
- ✅ `tables` ve `columns` kayıtlarını ekler (UI'da kullanılan tablolar)
- ✅ Foreign key ilişkisini kurar
- ✅ Örnek veriler ekler

---

## 📊 API Örnekleri

### Foreign Key Seçeneklerini Getir
```http
GET /api/foreign-key-options/users?display_column=name
```

**Response:**
```json
{
  "table": "users",
  "display_column": "name",
  "options": [
    { "value": 1, "label": "Ahmet Yılmaz" },
    { "value": 2, "label": "Ayşe Demir" }
  ]
}
```

### Tüm İlişkileri Listele
```http
GET /api/foreign-key-relations
```

**Response:**
```json
{
  "relations": [
    {
      "id": 15,
      "column_name": "user_id",
      "foreign_table": "users",
      "foreign_column": "id",
      "foreign_display_column": "name",
      "on_delete": "cascade"
    }
  ]
}
```

---

## 🎨 Frontend Özellikleri

### Form Alanları
- ✅ Foreign key alanları otomatik dropdown
- ✅ API'den dinamik veri yükleme
- ✅ Loading state desteği
- ✅ Hata yönetimi

### Tablo Görünümü
- ✅ Foreign key değerleri label olarak gösteriliyor
- ✅ Cache mekanizması ile performans
- ✅ Otomatik veri yükleme

---

## 📁 Değiştirilen Dosyalar

### Backend
- ✅ `backend/database/migrations/2026_01_18_202000_add_foreign_key_support_to_dynamic_columns.php`
- ✅ `backend/database/migrations/2026_01_18_203000_add_is_required_to_dynamic_columns.php`
- ✅ `backend/database/migrations/2026_01_18_204000_add_foreign_key_support_to_columns.php` ⭐
- ✅ `backend/app/Http/Controllers/Api/ForeignKeyController.php`
- ✅ `backend/routes/api.php`
- ✅ `backend/database/seeders/ForeignKeyExampleSeeder.php`

### Frontend
- ✅ `frontend/src/views/TableDetail.vue`

### Dokümantasyon
- ✅ `FOREIGN_KEY_GUIDE.md`
- ✅ `README_FOREIGN_KEY.md` (bu dosya)

---

## 🔥 Önemli Notlar

1. **Migration'ları çalıştırmayı unutma:**
   ```bash
   php artisan migrate
   ```

2. **Veritabanında constraint oluşturmayı unutma:**
   ```sql
   ALTER TABLE posts
   ADD CONSTRAINT fk_posts_user_id
   FOREIGN KEY (user_id) REFERENCES users(id);
   ```

3. **Frontend cache'i temizlemek için:**
   - Sayfa yenilendiğinde cache otomatik temizlenir
   - Route değiştiğinde cache sıfırlanır

---

## 🎉 Sonuç

Artık sisteminiz **tablolar arası ilişkileri (foreign key)** tam olarak destekliyor!

- ✅ Backend API hazır
- ✅ Frontend otomatik dropdown
- ✅ Tablo görünümünde label gösterimi
- ✅ Cache mekanizması
- ✅ Örnek seeder
- ✅ Detaylı dokümantasyon

**İleride ihtiyacınız olduğunda bu özelliği kullanabilirsiniz!** 🚀
