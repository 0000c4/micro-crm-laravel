<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductMovementController extends Controller
{
    /**
     * Получить историю движений товаров с фильтрацией и пагинацией.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductMovement::with(['product', 'warehouse', 'order']);
        
        // Применяем фильтры
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        
        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }
        
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }
        
        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        
        // Настраиваем сортировку
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Настраиваем пагинацию
        $perPage = $request->get('per_page', 15);
        $movements = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $movements
        ]);
    }
}