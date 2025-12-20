<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'permission_set_id'];

    public function permissionSet()
    {
        return $this->belongsTo(PermissionSet::class);
    }
}
