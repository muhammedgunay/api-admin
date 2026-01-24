# Foreign Key Kullanım Kılavuzu

## 🔗 Foreign Key Nasıl Kullanılır?

Bu sistem artık tablolar arası ilişkileri (foreign key) destekliyor. Bir tablonun kolonunu başka bir tabloya bağlayabilirsiniz.

---

## 📋 Örnek Senaryo: Posts Tablosu

Diyelim ki bir **posts** (yazılar) tablonuz var ve her yazının bir yazarı (user) olsun istiyorsunuz.

### 1. Adım: Tablo Oluştur

Önce `posts` tablosunu oluşturun:

```sql
-- Migration veya dinamik tablo oluşturma ile
CREATE TABLE posts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    user_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    created_by BIGINT,
    updated_by BIGINT
);
```

### 2. Adım: Foreign Key Kolonunu Tanımla

`columns` tablosuna `user_id` kolonunu eklerken foreign key bilgilerini de ekleyin:

```sql
INSERT INTO columns (
    table_id,
    name,
    display_name,
    type,
    is_visible,
    is_editable,
    is_required,
    is_foreign_key,
    foreign_table,
    foreign_column,
    foreign_display_column,
    on_delete,
    on_update
) VALUES (
    1, -- posts tablosunun ID'si
    'user_id',
    'Yazar',
    'bigint',
    true,
    true,
    true,
    true, -- 🔗 Foreign key aktif
    'users', -- Referans verilen tablo
    'id', -- Referans verilen kolon
    'name', -- Dropdown'da gösterilecek kolon
    'cascade', -- Kullanıcı silinirse yazılar da silinsin
    'cascade' -- Kullanıcı güncellenirse yazılar da güncellensin
);
```

### 3. Adım: Veritabanında Foreign Key Constraint Ekle

```sql
ALTER TABLE posts
ADD CONSTRAINT fk_posts_user_id
FOREIGN KEY (user_id) REFERENCES users(id)
ON DELETE CASCADE
ON UPDATE CASCADE;
```

---

## 🎯 Frontend'de Nasıl Görünür?

Artık `posts` tablosunda yeni bir kayıt eklemeye çalıştığınızda:

1. **Yazar** alanı bir **dropdown** olarak görünecek
2. Dropdown içinde tüm kullanıcılar listelenecek
3. Her kullanıcı için `name` kolonu gösterilecek (foreign_display_column)
4. Seçim yapıldığında `user_id` olarak kaydedilecek

---

## 🛠 API Endpoint'leri

### Foreign Key Seçeneklerini Getir

```http
GET /api/foreign-key-options/{table}?display_column={column}
```

**Örnek:**
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
    { "value": 2, "label": "Ayşe Demir" },
    { "value": 3, "label": "Mehmet Kaya" }
  ]
}
```

### Tüm Foreign Key İlişkilerini Listele

```http
GET /api/foreign-key-relations
```

**Response:**
```json
{
  "relations": [
    {
      "id": 15,
      "dynamic_table_id": 3,
      "column_name": "user_id",
      "foreign_table": "users",
      "foreign_column": "id",
      "foreign_display_column": "name",
      "on_delete": "cascade",
      "on_update": "cascade"
    }
  ]
}
```

---

## 📊 Veritabanı Şeması

`columns` tablosuna eklenen yeni alanlar:

| Alan | Tip | Açıklama |
|------|-----|----------|
| `is_foreign_key` | boolean | Bu kolon foreign key mi? |
| `foreign_table` | string | Hangi tabloya referans veriyor? |
| `foreign_column` | string | Hangi kolona referans veriyor? (genelde 'id') |
| `foreign_display_column` | string | Dropdown'da hangi kolon gösterilsin? |
| `on_delete` | enum | Silme davranışı: cascade, set null, restrict, no action |
| `on_update` | enum | Güncelleme davranışı: cascade, set null, restrict, no action |

**Not:** Aynı alanlar `dynamic_columns` tablosuna da eklendi (gelecekte kullanılmak üzere).

---

## ⚙️ Cascade Davranışları

### ON DELETE

- **CASCADE**: Ana kayıt silinirse, bağlı kayıtlar da silinir
- **SET NULL**: Ana kayıt silinirse, foreign key NULL yapılır
- **RESTRICT**: Ana kayıt silinmez (bağlı kayıt varsa)
- **NO ACTION**: Hiçbir şey yapma

### ON UPDATE

- **CASCADE**: Ana kayıt güncellenirse, bağlı kayıtlar da güncellenir
- **SET NULL**: Ana kayıt güncellenirse, foreign key NULL yapılır
- **RESTRICT**: Ana kayıt güncellenmez (bağlı kayıt varsa)
- **NO ACTION**: Hiçbir şey yapma

---

## 🎨 Örnek Kullanım Senaryoları

### 1. Blog Sistemi
- `posts.user_id` → `users.id` (Yazar)
- `comments.post_id` → `posts.id` (Yorum)
- `comments.user_id` → `users.id` (Yorumcu)

### 2. E-Ticaret
- `products.category_id` → `categories.id` (Kategori)
- `orders.user_id` → `users.id` (Müşteri)
- `order_items.product_id` → `products.id` (Ürün)

### 3. Proje Yönetimi
- `tasks.project_id` → `projects.id` (Proje)
- `tasks.assigned_to` → `users.id` (Atanan Kişi)
- `projects.owner_id` → `users.id` (Proje Sahibi)

---

## 🚀 İleri Seviye: Migration ile Otomatik Oluşturma

Gelecekte bir migration oluşturup foreign key'leri otomatik ekleyebilirsiniz:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            
            // Foreign Key
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

---

## ✅ Özet

1. ✅ Foreign key desteği eklendi
2. ✅ Dropdown otomatik oluşturuluyor
3. ✅ API endpoint'leri hazır
4. ✅ Cascade davranışları ayarlanabilir
5. ✅ Frontend otomatik olarak foreign key'leri algılıyor

**Artık tablolar arası ilişkiler tamamen destekleniyor! 🎉**
