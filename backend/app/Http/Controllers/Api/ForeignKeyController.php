<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ForeignKeyController extends Controller
{
    /**
     * Belirtilen tablo için foreign key seçeneklerini getir
     * 
     * Örnek: GET /api/foreign-key-options/users?display_column=name
     * 
     * @param string $table
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOptions(string $table, Request $request)
    {
        // Tablo var mı kontrol et
        if (!Schema::hasTable($table)) {
            return response()->json([
                'error' => 'Tablo bulunamadı'
            ], 404);
        }

        // Hangi kolonu göstereceğiz? (default: 'name')
        $displayColumn = $request->query('display_column', 'name');
        
        // Eğer display_column yoksa, ilk string kolonu kullan
        if (!Schema::hasColumn($table, $displayColumn)) {
            $columns = Schema::getColumnListing($table);
            $displayColumn = $columns[1] ?? 'id'; // id'den sonraki ilk kolon
        }

        // ID kolonu var mı kontrol et
        if (!Schema::hasColumn($table, 'id')) {
            return response()->json([
                'error' => 'Tabloda id kolonu bulunamadı'
            ], 400);
        }

        try {
            // Verileri çek
            $options = DB::table($table)
                ->select('id', $displayColumn . ' as label')
                ->get()
                ->map(function ($item) {
                    return [
                        'value' => $item->id,
                        'label' => $item->label ?? "ID: {$item->id}"
                    ];
                });

            return response()->json([
                'table' => $table,
                'display_column' => $displayColumn,
                'options' => $options
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Veriler alınırken hata oluştu',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tüm tablolar için foreign key ilişkilerini listele
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRelations()
    {
        try {
            $relations = DB::table('dynamic_columns')
                ->where('is_foreign_key', true)
                ->whereNotNull('foreign_table')
                ->select([
                    'id',
                    'dynamic_table_id',
                    'name as column_name',
                    'foreign_table',
                    'foreign_column',
                    'foreign_display_column',
                    'on_delete',
                    'on_update'
                ])
                ->get();

            return response()->json([
                'relations' => $relations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'İlişkiler alınırken hata oluştu',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
