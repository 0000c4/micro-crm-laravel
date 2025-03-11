<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Exceptions\OrderException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected $stockService;
    
    /**
     * OrderService конструктор.
     *
     * @param StockService $stockService
     */
    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }
    
    /**
     * Создать новый заказ.
     *
     * @param array $data Данные заказа (customer, warehouse_id)
     * @param array $items Элементы заказа (product_id, count)
     * @return Order
     * @throws OrderException
     */
    public function createOrder(array $data, array $items): Order
    {
        // Проверяем наличие товаров на складе
        if (!$this->stockService->checkStockAvailability($data['warehouse_id'], $items)) {
            throw new OrderException('Недостаточно товаров на складе');
        }
        
        return DB::transaction(function () use ($data, $items) {
            // Создаем заказ
            $order = Order::create([
                'customer' => $data['customer'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'active',
                'created_at' => now(),
            ]);
            
            // Создаем элементы заказа
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'count' => $item['count']
                ]);
            }
            
            // Резервируем товары на складе
            $this->stockService->reserveStock(
                $data['warehouse_id'], 
                $items, 
                $order->id
            );
            
            return $order;
        });
    }
    
    /**
     * Обновить существующий заказ.
     *
     * @param int $orderId ID заказа
     * @param array $data Данные заказа (customer)
     * @param array $items Новые элементы заказа (product_id, count)
     * @return Order
     * @throws OrderException
     */
    public function updateOrder(int $orderId, array $data, array $items): Order
    {
        $order = Order::findOrFail($orderId);
        
        if ($order->status !== 'active') {
            throw new OrderException('Можно обновлять только активные заказы');
        }
        
        return DB::transaction(function () use ($order, $data, $items) {
            // Обновляем основные данные заказа
            $order->customer = $data['customer'] ?? $order->customer;
            $order->save();
            
            // Получаем текущие элементы заказа
            $currentItems = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'count' => $item->count
                ];
            })->toArray();
            
            // Обновляем остатки на складе
            $this->stockService->updateReservation(
                $order->warehouse_id,
                $currentItems,
                $items,
                $order->id
            );
            
            // Удаляем старые элементы заказа и создаем новые
            $order->items()->delete();
            
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'count' => $item['count']
                ]);
            }
            
            return $order;
        });
    }
    
    /**
     * Завершить заказ.
     *
     * @param int $orderId ID заказа
     * @return Order
     * @throws OrderException
     */
    public function completeOrder(int $orderId): Order
    {
        $order = Order::findOrFail($orderId);
        
        if ($order->status !== 'active') {
            throw new OrderException('Можно завершить только активный заказ');
        }
        
        $order->status = 'completed';
        $order->completed_at = now();
        $order->save();
        
        return $order;
    }
    
    /**
     * Отменить заказ.
     *
     * @param int $orderId ID заказа
     * @return Order
     * @throws OrderException
     */
    public function cancelOrder(int $orderId): Order
    {
        $order = Order::findOrFail($orderId);
        
        if ($order->status !== 'active') {
            throw new OrderException('Можно отменить только активный заказ');
        }
        
        return DB::transaction(function () use ($order) {
            $order->status = 'canceled';
            $order->save();
            
            // Возвращаем товары на склад
            $items = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'count' => $item->count
                ];
            })->toArray();
            
            $this->stockService->returnStock(
                $order->warehouse_id,
                $items,
                $order->id,
                'order_canceled'
            );
            
            return $order;
        });
    }
    
    /**
     * Возобновить отмененный заказ.
     *
     * @param int $orderId ID заказа
     * @return Order
     * @throws OrderException
     */
    public function resumeOrder(int $orderId): Order
    {
        $order = Order::findOrFail($orderId);
        
        if ($order->status !== 'canceled') {
            throw new OrderException('Можно возобновить только отмененный заказ');
        }
        
        // Проверяем наличие товаров на складе
        $items = $order->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'count' => $item->count
            ];
        })->toArray();
        
        if (!$this->stockService->checkStockAvailability($order->warehouse_id, $items)) {
            throw new OrderException('Недостаточно товаров на складе для возобновления заказа');
        }
        
        return DB::transaction(function () use ($order, $items) {
            $order->status = 'active';
            $order->save();
            
            // Резервируем товары на складе
            $this->stockService->reserveStock(
                $order->warehouse_id,
                $items,
                $order->id,
                'order_resumed'
            );
            
            return $order;
        });
    }
}