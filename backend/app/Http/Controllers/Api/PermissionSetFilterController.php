<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissionSetFilterController extends Controller
{
    /**
     * Permission Set'e atanmış filtreleri listele
     */
    public function index(\App\Models\PermissionSet $permissionSet)
    {
        $filters = $permissionSet->filters()
            ->get()
            ->map(function ($filter) {
                return [
                    'id' => $filter->id,
                    'name' => $filter->name,
                    'description' => $filter->description,
                    'table_name' => $filter->table_name,
                    'filter_type' => $filter->filter_type,
                    'is_active' => $filter->is_active,
                    // Pivot data
                    'pivot' => [
                        'table_name' => $filter->pivot->table_name,
                        'action' => $filter->pivot->action,
                        'priority' => $filter->pivot->priority,
                    ]
                ];
            });

        return response()->json($filters);
    }

    /**
     * Permission Set'e filtre ata
     */
    public function attach(Request $request, \App\Models\PermissionSet $permissionSet)
    {
        $validated = $request->validate([
            'filter_id' => 'required|exists:filters,id',
            'table_name' => 'required|string',
            'action' => 'required|in:list,create,update,delete',
            'priority' => 'integer|min:0|max:100',
        ]);

        // Aynı filtre zaten atanmış mı kontrol et
        $exists = $permissionSet->filters()
            ->where('filter_id', $validated['filter_id'])
            ->wherePivot('table_name', $validated['table_name'])
            ->wherePivot('action', $validated['action'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Bu filtre zaten bu tablo ve action için atanmış.'
            ], 422);
        }

        // Filtreyi ata
        $permissionSet->filters()->attach($validated['filter_id'], [
            'table_name' => $validated['table_name'],
            'action' => $validated['action'],
            'priority' => $validated['priority'] ?? 0,
        ]);

        return response()->json([
            'message' => 'Filtre başarıyla atandı.'
        ], 201);
    }

    /**
     * Permission Set'ten filtre çıkar
     */
    public function detach(Request $request, \App\Models\PermissionSet $permissionSet)
    {
        $validated = $request->validate([
            'filter_id' => 'required|exists:filters,id',
            'table_name' => 'required|string',
            'action' => 'required|in:list,create,update,delete',
        ]);

        // Pivot kaydını bul ve sil
        \Illuminate\Support\Facades\DB::table('permission_set_filters')
            ->where('permission_set_id', $permissionSet->id)
            ->where('filter_id', $validated['filter_id'])
            ->where('table_name', $validated['table_name'])
            ->where('action', $validated['action'])
            ->delete();

        return response()->json([
            'message' => 'Filtre başarıyla kaldırıldı.'
        ]);
    }

    /**
     * Filtre atamasını güncelle (priority, table, action)
     */
    public function update(Request $request, \App\Models\PermissionSet $permissionSet)
    {
        $validated = $request->validate([
            'filter_id' => 'required|exists:filters,id',
            'old_table_name' => 'required|string',
            'old_action' => 'required|in:list,create,update,delete',
            'table_name' => 'required|string',
            'action' => 'required|in:list,create,update,delete',
            'priority' => 'integer|min:0|max:100',
        ]);

        // Eski kaydı bul ve güncelle
        $updated = \Illuminate\Support\Facades\DB::table('permission_set_filters')
            ->where('permission_set_id', $permissionSet->id)
            ->where('filter_id', $validated['filter_id'])
            ->where('table_name', $validated['old_table_name'])
            ->where('action', $validated['old_action'])
            ->update([
                'table_name' => $validated['table_name'],
                'action' => $validated['action'],
                'priority' => $validated['priority'] ?? 0,
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'message' => 'Filtre ataması bulunamadı.'
            ], 404);
        }

        return response()->json([
            'message' => 'Filtre ataması başarıyla güncellendi.'
        ]);
    }

    /**
     * Belirli bir tablo ve action için atanmış filtreleri getir
     */
    public function getByTableAndAction(Request $request, \App\Models\PermissionSet $permissionSet)
    {
        $validated = $request->validate([
            'table_name' => 'required|string',
            'action' => 'required|in:list,create,update,delete',
        ]);

        $filters = $permissionSet->filters()
            ->wherePivot('table_name', $validated['table_name'])
            ->wherePivot('action', $validated['action'])
            ->orderBy('permission_set_filters.priority', 'desc')
            ->get();

        return response()->json($filters);
    }
}

