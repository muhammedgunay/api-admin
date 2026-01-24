<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    protected $fillable = [
        'name',
        'description',
        'table_name',
        'filter_type',
        'sql_where_clause',
        'json_rules',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'json_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Boot method to automatically set audit columns
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
            if (auth()->check() && !$model->updated_by) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Permission Sets ilişkisi
     */
    public function permissionSets()
    {
        return $this->belongsToMany(PermissionSet::class, 'permission_set_filters')
            ->withPivot('table_name', 'action', 'priority')
            ->withTimestamps();
    }

    /**
     * Filtreyi SQL WHERE clause'a çevir
     * 
     * @param User $user Mevcut kullanıcı (placeholder replacement için)
     * @return string SQL WHERE clause
     */
    public function toSqlWhere(User $user): string
    {
        if (!$this->is_active) {
            return '1=1'; // Filtre aktif değilse, tüm kayıtları göster
        }

        if ($this->filter_type === 'sql') {
            return $this->replacePlaceholders($this->sql_where_clause, $user);
        }

        if ($this->filter_type === 'json' && $this->json_rules) {
            return $this->jsonToSql($this->json_rules, $user);
        }

        return '1=1'; // Varsayılan: filtre yok
    }

    /**
     * SQL içindeki placeholder'ları değiştir
     * 
     * @param string $sql SQL WHERE clause
     * @param User $user Mevcut kullanıcı
     * @return string Placeholder'lar değiştirilmiş SQL
     */
    private function replacePlaceholders(string $sql, User $user): string
    {
        $replacements = [
            '{user_id}' => $user->id,
            '{user_email}' => "'" . addslashes($user->email) . "'",
            '{user_name}' => "'" . addslashes($user->name) . "'",
            '{today}' => "'" . now()->toDateString() . "'",
            '{now}' => "'" . now()->toDateTimeString() . "'",
            '{current_year}' => now()->year,
            '{current_month}' => now()->month,
        ];

        // Kullanıcının custom field'ları varsa ekle
        // Department ID (eğer user'da department_id varsa)
        if (isset($user->department_id)) {
            $replacements['{user_department}'] = $user->department_id;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $sql);
    }

    /**
     * JSON rules'ı SQL WHERE clause'a çevir
     * 
     * @param array $rules JSON kuralları
     * @param User $user Mevcut kullanıcı
     * @return string SQL WHERE clause
     */
    private function jsonToSql(array $rules, User $user): string
    {
        if (empty($rules)) {
            return '1=1';
        }

        $condition = $rules['condition'] ?? 'AND';
        $rulesList = $rules['rules'] ?? [];

        if (empty($rulesList)) {
            return '1=1';
        }

        $sqlParts = [];

        foreach ($rulesList as $rule) {
            // Nested rules (grup)
            if (isset($rule['condition']) && isset($rule['rules'])) {
                $sqlParts[] = '(' . $this->jsonToSql($rule, $user) . ')';
                continue;
            }

            // Basit rule
            $field = $rule['field'] ?? '';
            $operator = $rule['operator'] ?? 'equals';
            $value = $rule['value'] ?? '';
            $valueType = $rule['value_type'] ?? 'static';

            if (empty($field)) {
                continue;
            }

            // Value type'a göre değeri hazırla
            if ($valueType === 'placeholder') {
                $value = $this->getPlaceholderValue($value, $user);
            } else {
                $value = $this->escapeValue($value);
            }

            // Operator'e göre SQL oluştur
            $sqlParts[] = $this->buildSqlCondition($field, $operator, $value);
        }

        if (empty($sqlParts)) {
            return '1=1';
        }

        return '(' . implode(' ' . $condition . ' ', $sqlParts) . ')';
    }

    /**
     * Placeholder değerini al
     */
    private function getPlaceholderValue(string $placeholder, User $user)
    {
        $values = [
            '{user_id}' => $user->id,
            '{user_email}' => "'" . addslashes($user->email) . "'",
            '{user_name}' => "'" . addslashes($user->name) . "'",
            '{today}' => "'" . now()->toDateString() . "'",
            '{now}' => "'" . now()->toDateTimeString() . "'",
        ];

        return $values[$placeholder] ?? "'{$placeholder}'";
    }

    /**
     * Değeri escape et
     */
    private function escapeValue($value)
    {
        if (is_array($value)) {
            return array_map(fn($v) => "'" . addslashes($v) . "'", $value);
        }

        if (is_numeric($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_null($value)) {
            return 'NULL';
        }

        return "'" . addslashes($value) . "'";
    }

    /**
     * SQL condition oluştur
     */
    private function buildSqlCondition(string $field, string $operator, $value): string
    {
        switch ($operator) {
            case 'equals':
                return "{$field} = {$value}";
            
            case 'not_equals':
                return "{$field} != {$value}";
            
            case 'greater_than':
                return "{$field} > {$value}";
            
            case 'less_than':
                return "{$field} < {$value}";
            
            case 'greater_or_equal':
                return "{$field} >= {$value}";
            
            case 'less_or_equal':
                return "{$field} <= {$value}";
            
            case 'in':
                if (is_array($value)) {
                    $values = implode(', ', $value);
                    return "{$field} IN ({$values})";
                }
                return "{$field} IN ({$value})";
            
            case 'not_in':
                if (is_array($value)) {
                    $values = implode(', ', $value);
                    return "{$field} NOT IN ({$values})";
                }
                return "{$field} NOT IN ({$value})";
            
            case 'like':
                return "{$field} LIKE {$value}";
            
            case 'not_like':
                return "{$field} NOT LIKE {$value}";
            
            case 'is_null':
                return "{$field} IS NULL";
            
            case 'is_not_null':
                return "{$field} IS NOT NULL";
            
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    return "{$field} BETWEEN {$value[0]} AND {$value[1]}";
                }
                return '1=1';
            
            default:
                return "{$field} = {$value}";
        }
    }
}

