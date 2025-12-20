<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionSet extends Model
{
    protected $fillable = ['name', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
