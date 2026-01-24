# 🔧 PostgreSQL Uyumluluk Düzeltmesi

## ❌ Sorun

FilterController'daki `getTables()` metodu MySQL komutu kullanıyordu:
```php
$tables = DB::select('SHOW TABLES');
```

Bu komut **sadece MySQL'de** çalışır. PostgreSQL'de hata verir:
```
SQLSTATE[42704]: Undefined object: 7 ERROR: unrecognized configuration parameter "tables"
```

## ✅ Çözüm

Laravel'in **Schema Builder**'ını kullanarak hem MySQL hem PostgreSQL uyumlu hale getirdik:

```php
$tableNames = Schema::getTableListing();
```

## 🎯 Değişiklikler

### Öncesi (Sadece MySQL):
```php
public function getTables()
{
    $tables = DB::select('SHOW TABLES');
    $databaseName = env('DB_DATABASE');
    $tableKey = "Tables_in_{$databaseName}";
    
    $tableNames = array_map(function($table) use ($tableKey) {
        return $table->$tableKey;
    }, $tables);
    
    // ...
}
```

### Sonrası (MySQL + PostgreSQL):
```php
public function getTables()
{
    try {
        // Laravel Schema Builder kullan (hem MySQL hem PostgreSQL uyumlu)
        $tableNames = Schema::getTableListing();
        
        // Sistem tablolarını filtrele
        $tableNames = array_filter($tableNames, function($table) {
            return !in_array($table, [
                'migrations',
                'password_reset_tokens',
                'password_resets',
                'sessions',
                'cache',
                'cache_locks',
                'jobs',
                'job_batches',
                'failed_jobs',
                'personal_access_tokens',
            ]);
        });
        
        return response()->json([
            'tables' => array_values($tableNames)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Tablolar yüklenirken hata oluştu: ' . $e->getMessage()
        ], 500);
    }
}
```

## 📊 Desteklenen Veritabanları

Artık şu veritabanları destekleniyor:
- ✅ PostgreSQL
- ✅ MySQL
- ✅ SQLite
- ✅ SQL Server

## 🧪 Test

Filters sayfasını açın ve "Yeni Filtre" butonuna tıklayın. Tablo dropdown'ı artık çalışmalı.

**Endpoint:**
```
GET /api/filters/tables
```

**Beklenen Yanıt:**
```json
{
  "tables": [
    "users",
    "roles",
    "permissions",
    "permission_sets",
    "filters",
    "permission_set_filters",
    ...
  ]
}
```

## 🔍 Ek Bilgi

### Schema Builder Avantajları:
1. **Veritabanı Bağımsız**: Tüm major veritabanlarında çalışır
2. **Laravel Native**: Framework'ün kendi API'si
3. **Güvenli**: SQL injection'a karşı korumalı
4. **Bakımı Kolay**: Veritabanı değişse bile kod aynı kalır

### Filtrelenen Sistem Tabloları:
- `migrations` - Laravel migration geçmişi
- `password_reset_tokens` - Şifre sıfırlama
- `sessions` - Oturum verileri
- `cache` - Cache verileri
- `jobs` - Queue işleri
- `failed_jobs` - Başarısız işler
- `personal_access_tokens` - API token'ları

Bu tablolar kullanıcıya gösterilmez çünkü sistem tabloları ve filtre oluşturmak için uygun değiller.
