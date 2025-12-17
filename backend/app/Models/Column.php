<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Column extends Model
{
    protected $fillable = ['table_id', 'name', 'display_name', 'type', 'is_visible', 'is_editable'];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}