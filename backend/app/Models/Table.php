<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];

    public function columns()
    {
        return $this->hasMany(Column::class);
    }
}