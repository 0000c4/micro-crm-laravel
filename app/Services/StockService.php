<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\ProductMovement;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Проверить, достаточно ли запасов на складе.
     *
     * @param int $warehouseId
     * @param array $items Массив элементов с product_id и count
     * @return bool
     */
    public function checkStockAvailability(int $warehouseId, array $items): bool
    {
        foreach ($items as $item) {
            $stock = Stock::where('product_id', $item['product_id'])
                ->where('warehouse_id', $warehouseId)
                ->first();
                
            if (!$stock || $stock->stock < $item['count']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Резервировать продукты для заказа (уменьшить остатки).
     *
     * @param int $warehouseId
     * @param array $items Массив элементов с product_id и count
     * @param int $orderId
     * @param string $movementType
     * @throws InsufficientStockException
     * @return void
     */
    public function reserveStock(int $warehouseId, array $items, int $orderId, string $movementType = 'order_created'): void
    {
        DB::transaction(function () use ($warehouseId, $items, $orderId, $movementType) {
            foreach ($items as $item) {
                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();
                
                if (!$stock || $stock->stock < $item['count']) {
                    throw new InsufficientStockException("Недостаточно товара на складе: {$item['product_id']}");
                }
                
                // Обновляем остаток
                $oldStock = $stock->stock;
                $stock->stock -= $item['count'];
                $stock->save();
                
                // Записываем движение
                ProductMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'quantity_change' => -$item['count'],
                    'quantity_after' => $stock->stock,
                    'order_id' => $orderId,
                    'movement_type' => $movementType
                ]);
            }
        });
    }
    
    /**
     * Вернуть продукты из заказа на склад (увеличить остатки).
     *
     * @param int $warehouseId
     * @param array $items Массив элементов с product_id и count
     * @param int $orderId
     * @param string $movementType
     * @return void
     */
    public function returnStock(int $warehouseId, array $items, int $orderId, string $movementType = 'order_canceled'): void
    {
        DB::transaction(function () use ($warehouseId, $items, $orderId, $movementType) {
            foreach ($items as $item) {
                $stock = Stock::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();
                
                if (!$stock) {
                    // Если по какой-то причине записи об остатке нет, создаем её
                    $stock = new Stock([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $warehouseId,
                        'stock' => 0
                    ]);
                }
                
                // Обновляем остаток
                $oldStock = $stock->stock;
                $stock->stock += $item['count'];
                $stock->save();
                
                // Записываем движение
                ProductMovement::create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'quantity_change' => $item['count'],
                    'quantity_after' => $stock->stock,
                    'order_id' => $orderId,
                    'movement_type' => $movementType
                ]);
            }
        });
    }
    
    /**
     * Обновить резервирование товаров для заказа.
     *
     * @param int $warehouseId
     * @param array $oldItems Старые элементы заказа
     * @param array $newItems Новые элементы заказа
     * @param int $orderId
     * @throws InsufficientStockException
     * @return void
     */
    public function updateReservation(int $warehouseId, array $oldItems, array $newItems, int $orderId): void
    {
        DB::transaction(function () use ($warehouseId, $oldItems, $newItems, $orderId) {
            // Формируем дельты для каждого продукта
            $deltas = [];
            
            // Сначала возвращаем все старые элементы в общий пул
            foreach ($oldItems as $item) {
                $deltas[$item['product_id']] = ($deltas[$item['product_id']] ?? 0) + $item['count'];
            }
            
            // Затем вычитаем новые элементы
            foreach ($newItems as $item) {
                $deltas[$item['product_id']] = ($deltas[$item['product_id']] ?? 0) - $item['count'];
            }
            
            // Проверяем, хватит ли остатков после применения дельт
            foreach ($deltas as $productId => $delta) {
                if ($delta < 0) {
                    // Нам нужно взять больше товара, чем было
                    $stock = Stock::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$stock || $stock->stock < abs($delta)) {
                        throw new InsufficientStockException("Недостаточно товара на складе: {$productId}");
                    }
                }
            }
            
            // Применяем дельты и записываем движения
            foreach ($deltas as $productId => $delta) {
                if ($delta == 0) continue; // Нет изменений для этого продукта
                
                $stock = Stock::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->first();
                
                if (!$stock && $delta < 0) {
                    throw new InsufficientStockException("Недостаточно товара на складе: {$productId}");
                }
                
                if (!$stock) {
                    // Если по какой-то причине записи об остатке нет, создаем её
                    $stock = new Stock([
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                        'stock' => 0
                    ]);
                }
                
                // Обновляем остаток
                $stock->stock += $delta;
                $stock->save();
                
                // Записываем движение
                ProductMovement::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity_change' => $delta,
                    'quantity_after' => $stock->stock,
                    'order_id' => $orderId,
                    'movement_type' => 'order_updated'
                ]);
            }
        });
    }
}