<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Filter::query();

        // Tablo adına göre filtrele
        if ($request->has('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        // Filtre tipine göre filtrele
        if ($request->has('filter_type')) {
            $query->where('filter_type', $request->filter_type);
        }

        // Aktif/pasif filtreleme
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sayfalama veya tümünü getir
        if ($request->has('paginate') && $request->paginate === 'false') {
            return response()->json($query->get());
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'table_name' => 'required|string|max:255',
            'filter_type' => 'required|in:sql,json',
            'sql_where_clause' => 'required_if:filter_type,sql|nullable|string',
            'json_rules' => 'required_if:filter_type,json|nullable|array',
            'is_active' => 'boolean',
        ]);

        // SQL filtre için placeholder validasyonu
        if ($validated['filter_type'] === 'sql') {
            $this->validateSqlPlaceholders($validated['sql_where_clause']);
        }

        $filter = \App\Models\Filter::create($validated);

        return response()->json([
            'message' => 'Filtre başarıyla oluşturuldu.',
            'filter' => $filter
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Filter $filter)
    {
        return response()->json($filter);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Filter $filter)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'table_name' => 'sometimes|required|string|max:255',
            'filter_type' => 'sometimes|required|in:sql,json',
            'sql_where_clause' => 'required_if:filter_type,sql|nullable|string',
            'json_rules' => 'required_if:filter_type,json|nullable|array',
            'is_active' => 'boolean',
        ]);

        // SQL filtre için placeholder validasyonu
        if (isset($validated['filter_type']) && $validated['filter_type'] === 'sql') {
            $this->validateSqlPlaceholders($validated['sql_where_clause'] ?? '');
        }

        $filter->update($validated);

        return response()->json([
            'message' => 'Filtre başarıyla güncellendi.',
            'filter' => $filter
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Filter $filter)
    {
        $filter->delete();

        return response()->json([
            'message' => 'Filtre başarıyla silindi.'
        ]);
    }

    /**
     * Test/Preview filtre SQL çıktısı
     */
    public function testFilter(Request $request, \App\Models\Filter $filter)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Kullanıcı oturumu bulunamadı.'
            ], 401);
        }

        try {
            $sqlWhere = $filter->toSqlWhere($user);

            return response()->json([
                'filter_name' => $filter->name,
                'filter_type' => $filter->filter_type,
                'original' => $filter->filter_type === 'sql' 
                    ? $filter->sql_where_clause 
                    : $filter->json_rules,
                'compiled_sql' => $sqlWhere,
                'user_context' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Filtre derlenirken hata oluştu: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Tüm tabloları listele (filtre oluştururken kullanmak için)
     */
    public function getTables()
    {
        try {
            // Laravel Schema Builder kullan (hem MySQL hem PostgreSQL uyumlu)
            $tableNames = \Illuminate\Support\Facades\Schema::getTableListing();

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

    /**
     * Belirli bir tablonun kolonlarını getir
     */
    public function getTableColumns(Request $request)
    {
        $request->validate([
            'table_name' => 'required|string'
        ]);

        $tableName = $request->table_name;

        try {
            $columns = \Illuminate\Support\Facades\DB::getSchemaBuilder()->getColumnListing($tableName);

            return response()->json([
                'table_name' => $tableName,
                'columns' => $columns
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Tablo bulunamadı veya erişim hatası.'
            ], 404);
        }
    }

    /**
     * SQL placeholder validasyonu
     */
    private function validateSqlPlaceholders(string $sql)
    {
        $allowedPlaceholders = [
            '{user_id}',
            '{user_email}',
            '{user_name}',
            '{user_department}',
            '{today}',
            '{now}',
            '{current_year}',
            '{current_month}',
        ];

        // SQL içindeki tüm placeholder'ları bul
        preg_match_all('/\{([^}]+)\}/', $sql, $matches);
        $foundPlaceholders = $matches[0];

        foreach ($foundPlaceholders as $placeholder) {
            if (!in_array($placeholder, $allowedPlaceholders)) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    response()->json([
                        'message' => "Geçersiz placeholder: {$placeholder}",
                        'allowed_placeholders' => $allowedPlaceholders
                    ], 422)
                );
            }
        }
    }

    /**
     * Desteklenen placeholder'ları listele
     */
    public function getPlaceholders()
    {
        return response()->json([
            'placeholders' => [
                [
                    'name' => '{user_id}',
                    'description' => 'Mevcut kullanıcının ID\'si',
                    'example' => 'created_by = {user_id}'
                ],
                [
                    'name' => '{user_email}',
                    'description' => 'Mevcut kullanıcının email adresi',
                    'example' => 'email = {user_email}'
                ],
                [
                    'name' => '{user_name}',
                    'description' => 'Mevcut kullanıcının adı',
                    'example' => 'assigned_to = {user_name}'
                ],
                [
                    'name' => '{user_department}',
                    'description' => 'Mevcut kullanıcının department ID\'si',
                    'example' => 'department_id = {user_department}'
                ],
                [
                    'name' => '{today}',
                    'description' => 'Bugünün tarihi (YYYY-MM-DD)',
                    'example' => 'created_at >= {today}'
                ],
                [
                    'name' => '{now}',
                    'description' => 'Şu anki tarih ve saat',
                    'example' => 'updated_at <= {now}'
                ],
                [
                    'name' => '{current_year}',
                    'description' => 'Mevcut yıl',
                    'example' => 'YEAR(created_at) = {current_year}'
                ],
                [
                    'name' => '{current_month}',
                    'description' => 'Mevcut ay (1-12)',
                    'example' => 'MONTH(created_at) = {current_month}'
                ],
            ],
            'operators' => [
                'equals' => '=',
                'not_equals' => '!=',
                'greater_than' => '>',
                'less_than' => '<',
                'greater_or_equal' => '>=',
                'less_or_equal' => '<=',
                'in' => 'IN',
                'not_in' => 'NOT IN',
                'like' => 'LIKE',
                'not_like' => 'NOT LIKE',
                'is_null' => 'IS NULL',
                'is_not_null' => 'IS NOT NULL',
                'between' => 'BETWEEN',
            ]
        ]);
    }
}

