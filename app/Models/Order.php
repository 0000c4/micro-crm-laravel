<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    
    /**
     * Не использовать стандартные временные метки.
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * Атрибуты, которые можно массово назначать.
     *
     * @var array
     */
    protected $fillable = ['customer', 'warehouse_id', 'status', 'completed_at'];
    
    /**
     * Атрибуты, которые следует приводить к определенным типам.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    /**
     * Получить склад, связанный с этим заказом.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    
    /**
     * Получить элементы заказа.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    /**
     * Получить движения продуктов, связанные с этим заказом.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productMovements()
    {
        return $this->hasMany(ProductMovement::class);
    }
}