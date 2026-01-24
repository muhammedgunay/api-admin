<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Column extends Model
{
    protected $fillable = [
        'table_id', 
        'name', 
        'display_name', 
        'type', 
        'is_visible', 
        'is_editable', 
        'is_required',
        // Foreign key alanları
        'is_foreign_key',
        'foreign_table',
        'foreign_column',
        'foreign_display_column',
        'on_delete',
        'on_update',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}