<?php

use App\Http\Controllers\Api\Admin\RolePermissionSetController;
use App\Http\Controllers\Api\GenericCrudController;
use App\Http\Controllers\Api\DynamicTableController;
use App\Http\Controllers\Api\DynamicColumnController;
use App\Http\Controllers\Api\AdminPermissionController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
    Route::post('/users/{user}/give-permission', [UserController::class, 'givePermission']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/{table}', [GenericCrudController::class, 'index']);
    Route::post('/{table}', [GenericCrudController::class, 'store']);
    Route::get('/{table}/{id}', [GenericCrudController::class, 'show']);
    Route::put('/{table}/{id}', [GenericCrudController::class, 'update']);
    Route::delete('/{table}/{id}', [GenericCrudController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/admin/permissions', [AdminPermissionController::class, 'store']);
    Route::get('/admin/permissions', [AdminPermissionController::class, 'index']);
    Route::get('/admin/permissions/{id}', [AdminPermissionController::class, 'show']);
});
//yetki verme (role permission set)
Route::middleware('auth:sanctum')->post(
    '/admin/roles/assign-permission-set',
    [RolePermissionSetController::class, 'assign']
);


Route::middleware('auth:sanctum')->group(function () {
    // Tables
    Route::get('/tables', [TableController::class, 'index']);
    Route::post('/tables', [TableController::class, 'store']);
    Route::put('/tables/{table}', [TableController::class, 'update']);
    Route::delete('/tables/{table}', [TableController::class, 'destroy']);

    // Columns (her tabloya özel)
    Route::get('/tables/{table}/columns', [ColumnController::class, 'index']);
    Route::post('/tables/{table}/columns', [ColumnController::class, 'store']);
    Route::put('/tables/{table}/columns/{column}', [ColumnController::class, 'update']);
    Route::delete('/tables/{table}/columns/{column}', [ColumnController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dynamic-tables', [DynamicTableController::class, 'store']);
});

Route::post(
    '/dynamic-tables/{table}/columns',
    [DynamicColumnController::class, 'store']
);
