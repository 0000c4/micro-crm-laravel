<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    
    /**
     * Первичный ключ не автоинкрементный.
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * Первичный ключ - составной.
     *
     * @var array
     */
    protected $primaryKey = ['product_id', 'warehouse_id'];
    
    /**
     * Атрибуты, которые можно массово назначать.
     *
     * @var array
     */
    protected $fillable = ['product_id', 'warehouse_id', 'stock'];
    
    /**
     * Получить продукт, связанный с этим остатком.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Получить склад, связанный с этим остатком.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}