<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class DynamicColumn extends Model
{
    protected $fillable = [
        'dynamic_table_id',
        'name',
        'label',
        'type',
        'is_nullable',
        'is_visible',
        'is_editable',
        'is_sortable',
        'is_filterable',
        'order_index',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'is_nullable' => 'boolean',
        'is_visible' => 'boolean',
        'is_editable' => 'boolean',
    ];

    public function table()
    {
        return $this->belongsTo(DynamicTable::class, 'dynamic_table_id');
    }
}
