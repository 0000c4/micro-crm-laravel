<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    /**
     * Атрибуты, которые можно массово назначать.
     *
     * @var array
     */
    protected $fillable = ['name', 'price'];
    
    /**
     * Получить все остатки этого продукта.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
    
    /**
     * Получить все элементы заказов, связанные с этим продуктом.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    /**
     * Получить все движения этого продукта.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productMovements()
    {
        return $this->hasMany(ProductMovement::class);
    }
}