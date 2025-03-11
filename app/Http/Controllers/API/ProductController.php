<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Получить список всех продуктов с их остатками по складам.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Получаем все продукты с присоединением остатков по всем складам
        $products = Product::with('stocks.warehouse')->get();
        
        // Преобразуем в формат, удобный для фронтенда
        $formattedProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stocks' => $product->stocks->map(function ($stock) {
                    return [
                        'warehouse_id' => $stock->warehouse_id,
                        'warehouse_name' => $stock->warehouse->name,
                        'stock' => $stock->stock
                    ];
                })
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedProducts
        ]);
    }
    
    /**
     * Получить информацию о конкретном продукте с его остатками.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with('stocks.warehouse')->findOrFail($id);
        
        $formattedProduct = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'stocks' => $product->stocks->map(function ($stock) {
                return [
                    'warehouse_id' => $stock->warehouse_id,
                    'warehouse_name' => $stock->warehouse->name,
                    'stock' => $stock->stock
                ];
            })
        ];
        
        return response()->json([
            'success' => true,
            'data' => $formattedProduct
        ]);
    }
}