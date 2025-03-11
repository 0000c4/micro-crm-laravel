<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Exceptions\OrderException;
use App\Exceptions\InsufficientStockException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $orderService;
    
    /**
     * OrderController конструктор.
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    
    /**
     * Получить список всех заказов с фильтрацией и пагинацией.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['warehouse', 'items.product']);
        
        // Применяем фильтры
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('customer')) {
            $query->where('customer', 'like', '%' . $request->customer . '%');
        }
        
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->has('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }
        
        if ($request->has('created_to')) {
            $query->where('created_at', '<=', $request->created_to);
        }
        
        // Настраиваем пагинацию
        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
    
    /**
     * Получить информацию о конкретном заказе.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with(['warehouse', 'items.product'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
    
    /**
     * Создать новый заказ.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Валидация входных данных
        $validator = Validator::make($request->all(), [
            'customer' => 'required|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.count' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $order = $this->orderService->createOrder(
                $request->only(['customer', 'warehouse_id']), 
                $request->items
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно создан',
                'data' => $order->load(['warehouse', 'items.product'])
            ], 201);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (OrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при создании заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Обновить существующий заказ.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Валидация входных данных
        $validator = Validator::make($request->all(), [
            'customer' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.count' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $order = $this->orderService->updateOrder(
                $id,
                $request->only(['customer']), 
                $request->items
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно обновлен',
                'data' => $order->load(['warehouse', 'items.product'])
            ]);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (OrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обновлении заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Завершить заказ.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->completeOrder($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно завершен',
                'data' => $order
            ]);
        } catch (OrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при завершении заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Отменить заказ.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->cancelOrder($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно отменен',
                'data' => $order
            ]);
        } catch (OrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отмене заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Возобновить отмененный заказ.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function resume(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->resumeOrder($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно возобновлен',
                'data' => $order
            ]);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (OrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при возобновлении заказа',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}