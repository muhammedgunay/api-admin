# 🏢 Department Bazlı Filtre Kullanım Kılavuzu

## 📋 Genel Bakış

Bu kılavuz, department (departman) bazlı filtreleme sisteminin nasıl kurulacağını ve kullanılacağını açıklar.

---

## 🎯 Senaryo

**Amaç:** Kullanıcılar sadece kendi departmanlarındaki kayıtları görebilsin.

**Örnek:**
- Ali, Sales departmanında
- Veli, IT departmanında
- Ali sadece Sales departmanındaki kullanıcıları görebilmeli
- Veli sadece IT departmanındaki kullanıcıları görebilmeli

---

## 🔧 Kurulum Adımları

### Adım 1: Users Tablosuna department_id Ekleyin

**Migration Oluştur:**
```bash
php artisan make:migration add_department_id_to_users_table
```

**Migration Dosyası:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('email');
            
            // Eğer departments tablosu varsa foreign key ekleyin
            // $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });
    }
};
```

**Migration'ı Çalıştır:**
```bash
php artisan migrate
```

### Adım 2: User Model'i Güncelleyin

**app/Models/User.php:**
```php
protected $fillable = [
    'name',
    'surname',
    'email',
    'password',
    'department_id',  // ← Ekleyin
    'created_by',
    'updated_by',
];
```

### Adım 3: Filtre Oluşturun

**UI'da Filters Sayfasına Gidin:**
1. Sidebar'dan "Filtreler" menüsüne tıklayın
2. "Yeni Filtre" butonuna tıklayın

**Filtre Bilgileri:**
- **Ad:** `Sadece Kendi Departmanı`
- **Açıklama:** `Kullanıcılar sadece kendi departmanlarındaki kayıtları görebilir`
- **Tablo:** `users`
- **Filtre Tipi:** `SQL WHERE Clause`
- **SQL WHERE Clause:**
  ```sql
  department_id = {user_department}
  ```
- **Aktif:** ✅ İşaretli

**Kaydet** butonuna tıklayın.

### Adım 4: Filtreyi Permission Set'e Atayın

*(Bu kısım bir sonraki fazda eklenecek, şimdilik filtre hazır)*

---

## 📖 SQL WHERE Clause Örnekleri

### 1️⃣ Basit Department Filtresi
```sql
department_id = {user_department}
```

**Açıklama:** Kullanıcının kendi department_id'si ile eşleşen kayıtları gösterir.

**Derlenmiş SQL (user department_id = 3 için):**
```sql
SELECT * FROM users WHERE department_id = 3
```

---

### 2️⃣ Department + Aktif Kullanıcılar
```sql
department_id = {user_department} AND status = 'active'
```

**Açıklama:** Hem aynı departmanda hem de aktif olan kullanıcıları gösterir.

---

### 3️⃣ Birden Fazla Department (Subquery)
```sql
department_id IN (SELECT department_id FROM user_departments WHERE user_id = {user_id})
```

**Açıklama:** Kullanıcının erişebildiği tüm departmanları gösterir (pivot tablo varsa).

---

### 4️⃣ Department veya Kendi Kayıtları
```sql
department_id = {user_department} OR created_by = {user_id}
```

**Açıklama:** Ya aynı departmandaki kayıtları ya da kullanıcının kendi oluşturduğu kayıtları gösterir.

---

### 5️⃣ Department Hiyerarşisi (Parent Department)
```sql
department_id IN (
    SELECT id FROM departments 
    WHERE id = {user_department} 
    OR parent_id = {user_department}
)
```

**Açıklama:** Kullanıcının departmanı ve alt departmanlarını gösterir.

---

## 🧪 Filtreyi Test Etme

### UI'da Test:
1. Filters sayfasında filtrenizi bulun
2. 🧪 (Test Et) butonuna tıklayın
3. Derlenmiş SQL'i görün

**Örnek Test Sonucu:**
```json
{
  "filter_name": "Sadece Kendi Departmanı",
  "filter_type": "sql",
  "user_context": {
    "user_id": 1,
    "user_email": "admin@example.com",
    "user_name": "Admin"
  },
  "original": "department_id = {user_department}",
  "compiled_sql": "department_id = 3"
}
```

---

## 🎯 Kullanılabilir Placeholder'lar

| Placeholder | Açıklama | Örnek Kullanım |
|------------|----------|----------------|
| `{user_id}` | Mevcut kullanıcının ID'si | `created_by = {user_id}` |
| `{user_email}` | Mevcut kullanıcının email'i | `email = {user_email}` |
| `{user_name}` | Mevcut kullanıcının adı | `assigned_to = {user_name}` |
| `{user_department}` | Mevcut kullanıcının department ID'si | `department_id = {user_department}` |
| `{today}` | Bugünün tarihi | `created_at >= {today}` |
| `{now}` | Şu anki tarih ve saat | `updated_at <= {now}` |
| `{current_year}` | Mevcut yıl | `YEAR(created_at) = {current_year}` |
| `{current_month}` | Mevcut ay | `MONTH(created_at) = {current_month}` |

---

## 💡 İpuçları

### ✅ Doğru Kullanım:
```sql
department_id = {user_department}
status = 'active'
created_at >= {today}
```

### ❌ Yanlış Kullanım:
```sql
department_id = {user_department_id}  ❌ (Yanlış placeholder)
status = active                        ❌ (String değerler tırnak içinde olmalı)
created_at >= today                    ❌ (Placeholder kullanın)
```

---

## 🔒 Güvenlik Notları

1. **SQL Injection Koruması:** Placeholder'lar otomatik olarak escape edilir
2. **Sadece Admin:** Filtreleri sadece admin kullanıcılar oluşturabilir
3. **Validation:** Sadece izin verilen placeholder'lar kullanılabilir
4. **Test Özelliği:** Filtreleri production'a almadan önce test edin

---

## 🚀 Sonraki Adımlar

1. ✅ Department_id kolonunu ekleyin
2. ✅ Filtreyi oluşturun
3. ✅ Filtreyi test edin
4. ⏳ Filtreyi Permission Set'e atayın (Faz 4)
5. ⏳ Filtreyi role atayın (Faz 4)

---

## 📞 Sık Sorulan Sorular

### S: User'ın department_id'si yoksa ne olur?
**C:** Filtre `NULL` değeri kullanır ve hiçbir kayıt gösterilmez. Bu yüzden tüm kullanıcılara department_id atamanız önerilir.

### S: Birden fazla filtre aynı anda çalışabilir mi?
**C:** Evet! Filtreler AND operatörü ile birleştirilir.

### S: Subquery kullanabilir miyim?
**C:** Evet! Karmaşık SQL sorguları desteklenir.

### S: JSON mode ne zaman kullanılmalı?
**C:** Şu anda sadece SQL mode aktif. JSON mode yakında eklenecek.

---

## 🎓 Örnek Senaryolar

### Senaryo 1: Sales Departmanı
```sql
-- Filtre Adı: Sales Departmanı Kayıtları
-- Tablo: orders
department_id = {user_department}
```

### Senaryo 2: Kendi Departmanı + Son 30 Gün
```sql
-- Filtre Adı: Departman Son 30 Gün
-- Tablo: reports
department_id = {user_department} 
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
```

### Senaryo 3: Manager Erişimi
```sql
-- Filtre Adı: Manager Departman Erişimi
-- Tablo: employees
department_id IN (
    SELECT id FROM departments 
    WHERE manager_id = {user_id}
)
```
