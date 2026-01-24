<?php

use App\Http\Controllers\Api\Admin\RolePermissionSetController;
use App\Http\Controllers\Api\GenericCrudController;
use App\Http\Controllers\Api\DynamicTableController;
use App\Http\Controllers\Api\DynamicColumnController;
use App\Http\Controllers\Api\AdminPermissionController;
use App\Http\Controllers\Api\ForeignKeyController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\ColumnController;
use App\Http\Controllers\Api\MeController;


Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Roles
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [RoleController::class, 'index']); // listele
    Route::post('/roles', [RoleController::class, 'store']); // ekle
    Route::put('/roles/{role}', [RoleController::class, 'update']); // güncelle
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']); // sil
});

// Permissions
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/permissions', [PermissionController::class, 'index']); // listele
    Route::post('/permissions', [PermissionController::class, 'store']); // ekle
    Route::put('/permissions/{permission}', [PermissionController::class, 'update']); // güncelle
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']); // sil
});

Route::middleware('auth:sanctum')->get(
    '/me/permissions',
    [MeController::class, 'permissions']
);

// Users
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/columns', [UserController::class, 'getColumns']); // kolonları getir
    Route::get('/users', [UserController::class, 'index']); // listele
    Route::post('/users', [UserController::class, 'store']); // ekle
    Route::put('/users/{user}', [UserController::class, 'update']); // güncelle
    Route::delete('/users/{user}', [UserController::class, 'destroy']); // sil
    Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']); // (Opsiyonel: spesifik endpoint kalsın mı?)
    Route::post('/users/{user}/give-permission', [UserController::class, 'givePermission']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/permissions', [AdminPermissionController::class, 'index']);
    Route::post('/admin/permissions', [AdminPermissionController::class, 'store']);
    Route::get('/admin/permissions/{id}', [AdminPermissionController::class, 'show']);
    Route::put('/admin/permissions/{id}', [AdminPermissionController::class, 'update']);
    Route::delete('/admin/permissions/{id}', [AdminPermissionController::class, 'destroy']);
});
//yetki verme (role permission set)
Route::middleware('auth:sanctum')->post(
    '/admin/roles/assign-permission-set',
    [RolePermissionSetController::class, 'assign']
);

// ✅ Spesifik route'lar önce gelmeli (generic route'lardan önce)
Route::middleware('auth:sanctum')->group(function () {
    // Tables
    Route::get('/tables', [TableController::class, 'index'])->name('api.tables.index');
    Route::post('/tables', [TableController::class, 'store']);
    Route::put('/tables/{table}', [TableController::class, 'update']);
    Route::post('/tables/{table}/fix', [TableController::class, 'fix']); // Veritabanı tablosunu oluştur
    Route::delete('/tables/{table}', [TableController::class, 'destroy']);

    // Columns
    Route::get('/columns', [ColumnController::class, 'all']); // Tüm kolonlar
    Route::get('/tables/{table}/columns', [ColumnController::class, 'index']); // Belirli tablonun kolonları
    Route::post('/tables/{table}/columns', [ColumnController::class, 'store']);
    Route::put('/tables/{table}/columns/{column}', [ColumnController::class, 'update']);
    Route::delete('/tables/{table}/columns/{column}', [ColumnController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dynamic-tables', [DynamicTableController::class, 'index']);
    Route::post('/dynamic-tables', [DynamicTableController::class, 'store']);
});

// Foreign Key Options (Generic route'lardan önce gelmeli)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/foreign-key-options/{table}', [ForeignKeyController::class, 'getOptions']);
    Route::get('/foreign-key-relations', [ForeignKeyController::class, 'getRelations']);
});

// Filters (Generic route'lardan önce gelmeli)
Route::middleware('auth:sanctum')->group(function () {
    // Helper endpoints
    Route::get('/filters/tables', [\App\Http\Controllers\Api\FilterController::class, 'getTables']);
    Route::get('/filters/table-columns', [\App\Http\Controllers\Api\FilterController::class, 'getTableColumns']);
    Route::get('/filters/placeholders', [\App\Http\Controllers\Api\FilterController::class, 'getPlaceholders']);
    
    // Test endpoint
    Route::post('/filters/{filter}/test', [\App\Http\Controllers\Api\FilterController::class, 'testFilter']);
    
    // CRUD operations
    Route::get('/filters', [\App\Http\Controllers\Api\FilterController::class, 'index']);
    Route::post('/filters', [\App\Http\Controllers\Api\FilterController::class, 'store']);
    Route::get('/filters/{filter}', [\App\Http\Controllers\Api\FilterController::class, 'show']);
    Route::put('/filters/{filter}', [\App\Http\Controllers\Api\FilterController::class, 'update']);
    Route::delete('/filters/{filter}', [\App\Http\Controllers\Api\FilterController::class, 'destroy']);
});

// Permission Set Filters (Generic route'lardan önce gelmeli)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/permission-sets/{permissionSet}/filters', [\App\Http\Controllers\Api\PermissionSetFilterController::class, 'index']);
    Route::post('/permission-sets/{permissionSet}/filters/attach', [\App\Http\Controllers\Api\PermissionSetFilterController::class, 'attach']);
    Route::post('/permission-sets/{permissionSet}/filters/detach', [\App\Http\Controllers\Api\PermissionSetFilterController::class, 'detach']);
    Route::put('/permission-sets/{permissionSet}/filters/update', [\App\Http\Controllers\Api\PermissionSetFilterController::class, 'update']);
    Route::get('/permission-sets/{permissionSet}/filters/by-table-action', [\App\Http\Controllers\Api\PermissionSetFilterController::class, 'getByTableAndAction']);
});

// ⚠️ Generic route'lar EN SONDA olmalı (spesifik route'lardan sonra)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/{table}', [GenericCrudController::class, 'index']);
    Route::post('/{table}', [GenericCrudController::class, 'store']);
    Route::get('/{table}/{id}', [GenericCrudController::class, 'show']);
    Route::put('/{table}/{id}', [GenericCrudController::class, 'update']);
    Route::delete('/{table}/{id}', [GenericCrudController::class, 'destroy']);
});

Route::post(
    '/dynamic-tables/{table}/columns',
    [DynamicColumnController::class, 'store']
);
