# 🔒 Dinamik Filtre Sistemi Tasarımı

## 📌 Genel Bakış

Low-code platformumuzda **row-level security** (satır seviyesi güvenlik) sağlamak için dinamik filtre sistemi.

### Kullanım Senaryoları:
- ✅ Kullanıcılar sadece kendi kayıtlarını görebilir (`created_by = {user_id}`)
- ✅ Departman yöneticileri sadece kendi departmanlarını görebilir (`department_id = {user_department}`)
- ✅ Belirli statüdeki kayıtlar (`status = 'active'`)
- ✅ Tarih aralığı filtreleri (`created_at >= '2024-01-01'`)
- ✅ Karmaşık kombinasyonlar (AND, OR, nested conditions)

---

## 🗄️ Veritabanı Yapısı

### 1. `filters` Tablosu
```sql
CREATE TABLE filters (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,           -- Filtre adı (ör: "Sadece Kendi Kayıtları")
    description TEXT,                      -- Açıklama
    table_name VARCHAR(255) NOT NULL,     -- Hangi tablo için (ör: "users")
    filter_type ENUM('sql', 'json') DEFAULT 'json',  -- Filtre tipi
    
    -- SQL Tabanlı Filtre
    sql_where_clause TEXT,                -- WHERE clause (ör: "created_by = {user_id}")
    
    -- JSON Tabanlı Filtre (Rule Builder)
    json_rules JSON,                       -- Yapılandırılmış kurallar
    
    -- Meta
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_table_name (table_name),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### 2. `permission_set_filters` Pivot Tablosu
```sql
CREATE TABLE permission_set_filters (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    permission_set_id BIGINT NOT NULL,
    filter_id BIGINT NOT NULL,
    table_name VARCHAR(255) NOT NULL,     -- Hangi tablo için geçerli
    action VARCHAR(50) NOT NULL,          -- Hangi action için (list, create, update, delete)
    priority INT DEFAULT 0,               -- Filtre önceliği (birden fazla filtre varsa)
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (permission_set_id) REFERENCES permission_sets(id) ON DELETE CASCADE,
    FOREIGN KEY (filter_id) REFERENCES filters(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_permission_filter (permission_set_id, filter_id, table_name, action)
);
```

---

## 🎯 Filtre Tipleri

### 1️⃣ SQL WHERE Clause (Esnek ve Güçlü)

**Örnek 1: Sadece kendi kayıtları**
```sql
created_by = {user_id}
```

**Örnek 2: Departman bazlı**
```sql
department_id IN (SELECT department_id FROM user_departments WHERE user_id = {user_id})
```

**Örnek 3: Karmaşık filtre**
```sql
(status = 'active' OR status = 'pending') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
```

**Placeholder'lar:**
- `{user_id}` - Mevcut kullanıcı ID
- `{user_email}` - Mevcut kullanıcı email
- `{user_role}` - Mevcut kullanıcı rolü
- `{user_department}` - Kullanıcının departmanı (custom field)
- `{today}` - Bugünün tarihi
- `{now}` - Şu anki zaman

### 2️⃣ JSON Rule Builder (Güvenli ve Yapılandırılmış)

**Örnek JSON:**
```json
{
  "condition": "AND",
  "rules": [
    {
      "field": "created_by",
      "operator": "equals",
      "value": "{user_id}",
      "value_type": "placeholder"
    },
    {
      "field": "status",
      "operator": "in",
      "value": ["active", "pending"],
      "value_type": "static"
    }
  ]
}
```

**Desteklenen Operatörler:**
- `equals`, `not_equals`
- `greater_than`, `less_than`, `greater_or_equal`, `less_or_equal`
- `in`, `not_in`
- `like`, `not_like`
- `is_null`, `is_not_null`
- `between`

---

## 🔧 Backend Implementasyonu

### Filter Model
```php
class Filter extends Model
{
    protected $fillable = [
        'name', 'description', 'table_name', 'filter_type',
        'sql_where_clause', 'json_rules', 'is_active'
    ];
    
    protected $casts = [
        'json_rules' => 'array',
        'is_active' => 'boolean'
    ];
    
    public function permissionSets()
    {
        return $this->belongsToMany(PermissionSet::class, 'permission_set_filters')
            ->withPivot('table_name', 'action', 'priority')
            ->withTimestamps();
    }
    
    /**
     * Filtreyi SQL WHERE clause'a çevir
     */
    public function toSqlWhere(User $user): string
    {
        if ($this->filter_type === 'sql') {
            return $this->replacePlaceholders($this->sql_where_clause, $user);
        }
        
        if ($this->filter_type === 'json') {
            return $this->jsonToSql($this->json_rules, $user);
        }
        
        return '1=1'; // No filter
    }
    
    private function replacePlaceholders(string $sql, User $user): string
    {
        $replacements = [
            '{user_id}' => $user->id,
            '{user_email}' => "'{$user->email}'",
            '{today}' => "'" . now()->toDateString() . "'",
            '{now}' => "'" . now()->toDateTimeString() . "'",
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $sql);
    }
    
    private function jsonToSql(array $rules, User $user): string
    {
        // JSON rules'ı SQL'e çevir
        // Implementasyon detayları...
    }
}
```

### Permission Helper Güncellemesi
```php
class PermissionHelper
{
    public static function getPermission(User $user, string $table, string $action)
    {
        // ... mevcut kod ...
        
        // Filtreleri al
        $filters = self::getFiltersForPermission($user, $table, $action);
        
        return [
            'columns' => $columns,
            'filters' => $filters  // SQL WHERE clause array
        ];
    }
    
    private static function getFiltersForPermission(User $user, string $table, string $action): array
    {
        $role = $user->roles()->first();
        if (!$role || !$role->permissionSet) {
            return [];
        }
        
        $filters = $role->permissionSet
            ->filters()
            ->where('permission_set_filters.table_name', $table)
            ->where('permission_set_filters.action', $action)
            ->where('filters.is_active', true)
            ->orderBy('permission_set_filters.priority', 'desc')
            ->get();
        
        return $filters->map(fn($f) => $f->toSqlWhere($user))->toArray();
    }
}
```

### Controller'da Kullanım
```php
public function index(Request $request)
{
    $table = 'users';
    $perm = $this->getPermission($table, 'list');
    abort_if(!$perm, 403);
    
    $query = DB::table($table)->select($perm['columns']);
    
    // Filtreleri uygula
    if (!empty($perm['filters'])) {
        foreach ($perm['filters'] as $filter) {
            $query->whereRaw($filter);
        }
    }
    
    return $query->paginate(10);
}
```

---

## 🎨 Frontend UI

### Filtre Yönetim Sayfası (`Filters.vue`)

**Özellikler:**
1. ✅ Filtre listesi
2. ✅ Yeni filtre ekleme
3. ✅ SQL veya JSON mode seçimi
4. ✅ SQL editor (syntax highlighting)
5. ✅ Visual rule builder (JSON mode)
6. ✅ Placeholder helper
7. ✅ Test/Preview özelliği

### Permission Set Sayfasında Filtre Atama

**Özellikler:**
1. ✅ Her tablo ve action için filtre seçimi
2. ✅ Birden fazla filtre atama
3. ✅ Filtre öncelik sıralaması
4. ✅ Filtre önizleme

---

## 🚀 Implementasyon Adımları

### Faz 1: Database & Models
1. ✅ `filters` migration oluştur
2. ✅ `permission_set_filters` migration oluştur
3. ✅ Filter model oluştur
4. ✅ PermissionSet model'e filters ilişkisi ekle

### Faz 2: Backend API
1. ✅ FilterController oluştur (CRUD)
2. ✅ Filter validation rules
3. ✅ SQL placeholder replacement
4. ✅ JSON to SQL converter
5. ✅ Permission helper güncelle

### Faz 3: Frontend
1. ✅ Filters.vue sayfası
2. ✅ SQL Editor component
3. ✅ Rule Builder component
4. ✅ PermissionSets.vue'ya filtre atama ekle

### Faz 4: Testing & Security
1. ✅ SQL injection koruması
2. ✅ Filtre test endpoint
3. ✅ Unit tests
4. ✅ Integration tests

---

## 🔐 Güvenlik Önlemleri

### SQL Injection Koruması
```php
// ❌ YANLIŞ - Direkt kullanıcı inputu
$sql = "status = '" . $request->input('status') . "'";

// ✅ DOĞRU - Sadece admin tanımlı filtreler
// Kullanıcılar sadece önceden tanımlanmış filtreleri seçebilir
// SQL'i sadece admin yazabilir ve veritabanında saklanır
```

### Placeholder Whitelist
```php
private const ALLOWED_PLACEHOLDERS = [
    '{user_id}',
    '{user_email}',
    '{today}',
    '{now}',
];

private function validatePlaceholders(string $sql): bool
{
    preg_match_all('/\{([^}]+)\}/', $sql, $matches);
    $found = $matches[0];
    
    foreach ($found as $placeholder) {
        if (!in_array($placeholder, self::ALLOWED_PLACEHOLDERS)) {
            throw new \Exception("Invalid placeholder: {$placeholder}");
        }
    }
    
    return true;
}
```

---

## 📊 Örnek Kullanım Senaryoları

### Senaryo 1: Departman Bazlı Erişim
```
Filtre: "Sadece Kendi Departmanı"
SQL: department_id = (SELECT department_id FROM users WHERE id = {user_id})
Atama: Sales role -> customers tablosu -> list action
```

### Senaryo 2: Zaman Bazlı Erişim
```
Filtre: "Son 30 Gün"
SQL: created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
Atama: Analyst role -> reports tablosu -> list action
```

### Senaryo 3: Sahiplik Bazlı
```
Filtre: "Sadece Kendi Kayıtları"
SQL: created_by = {user_id}
Atama: User role -> documents tablosu -> list, update, delete actions
```

---

## 🎯 Sonraki Adımlar

Hangi fazdan başlamak istersiniz?
1. **Database migrations** oluşturalım
2. **Backend API** (FilterController) yazalım
3. **Frontend UI** (Filters.vue) tasarlayalım

Önerim: Sırayla gidelim, önce veritabanı yapısını oluşturalım.
