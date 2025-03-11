<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    /**
     * Получить список всех складов.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::all();
        
        return response()->json([
            'success' => true,
            'data' => $warehouses
        ]);
    }
    
    /**
     * Получить информацию о конкретном складе.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $warehouse
        ]);
    }
}