<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class DynamicTable extends Model
{
    protected $fillable = [
        'name', 'label', 'is_active', 'order_index', 'created_by'
    ];

    public function columns()
    {
        return $this->hasMany(DynamicColumn::class)
            ->orderBy('order_index');
    }
}
