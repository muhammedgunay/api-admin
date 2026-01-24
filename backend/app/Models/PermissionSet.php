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

    /**
     * Filters ilişkisi
     */
    public function filters()
    {
        return $this->belongsToMany(Filter::class, 'permission_set_filters')
            ->withPivot('table_name', 'action', 'priority')
            ->withTimestamps()
            ->orderBy('permission_set_filters.priority', 'desc');
    }
}
