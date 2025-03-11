<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    
    /**
     * Атрибуты, которые можно массово назначать.
     *
     * @var array
     */
    protected $fillable = ['name'];
    
    /**
     * Получить все остатки, связанные с этим складом.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
    
    /**
     * Получить все заказы, связанные с этим складом.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Получить все движения продуктов на этом складе.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productMovements()
    {
        return $this->hasMany(ProductMovement::class);
    }
}