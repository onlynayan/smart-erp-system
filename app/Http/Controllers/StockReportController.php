<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $stockReport = $this->stockQuery()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where(function ($query) use ($search) {
                    $query->where('products.name', 'ilike', "%{$search}%")
                        ->orWhere('products.sku', 'ilike', "%{$search}%")
                        ->orWhere('products.part_number', 'ilike', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('products.category_id', $request->query('category_id'));
            })
            ->when($request->filled('brand_id'), function ($query) use ($request) {
                $query->where('products.brand_id', $request->query('brand_id'));
            })
            ->when($request->boolean('low_stock'), function ($query) {
                $query->whereRaw('(COALESCE(stock.total_stock_in, 0) - COALESCE(stock.total_stock_out, 0)) <= products.reorder_level');
            })
            ->orderBy('products.name')
            ->paginate($perPage);

        return $this->success('Stock report retrieved successfully.', $stockReport);
    }

    public function show(int $productId): JsonResponse
    {
        $stockReport = $this->stockQuery()
            ->where('products.id', $productId)
            ->first();

        if (! $stockReport) {
            return $this->error('Stock report product not found.', null, 404);
        }

        return $this->success('Stock report retrieved successfully.', $stockReport);
    }

    private function stockQuery()
    {
        $stockSummary = DB::table('inventory_ledger')
            ->select('product_id')
            ->selectRaw('COALESCE(SUM(stock_in), 0) as total_stock_in')
            ->selectRaw('COALESCE(SUM(stock_out), 0) as total_stock_out')
            ->groupBy('product_id');

        return DB::table('products')
            ->leftJoinSub($stockSummary, 'stock', function ($join) {
                $join->on('products.id', '=', 'stock.product_id');
            })
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.part_number',
                'products.reorder_level',
            ])
            ->selectRaw('COALESCE(stock.total_stock_in, 0) as total_stock_in')
            ->selectRaw('COALESCE(stock.total_stock_out, 0) as total_stock_out')
            ->selectRaw('(COALESCE(stock.total_stock_in, 0) - COALESCE(stock.total_stock_out, 0)) as current_stock')
            ->selectRaw("
                CASE
                    WHEN (COALESCE(stock.total_stock_in, 0) - COALESCE(stock.total_stock_out, 0)) <= products.reorder_level
                    THEN 'LOW_STOCK'
                    ELSE 'AVAILABLE'
                END as stock_status
            ");
    }

    private function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function error(string $message, mixed $data = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
