<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $salesTypeId = $this->transactionTypeId('SALES_INVOICE');
        $purchaseTypeId = $this->transactionTypeId('PURCHASE_RECEIVE');

        $data = [
            'total_products' => Product::count(),
            'total_customers' => Party::whereIn('party_type', ['customer', 'both'])->count(),
            'total_suppliers' => Party::whereIn('party_type', ['supplier', 'both'])->count(),
            'total_sales_amount' => $this->transactionSum($salesTypeId, 'grand_total'),
            'total_purchase_amount' => $this->transactionSum($purchaseTypeId, 'grand_total'),
            'total_receivable' => $this->transactionSum($salesTypeId, 'due_amount'),
            'total_payable' => $this->transactionSum($purchaseTypeId, 'due_amount'),
            'low_stock_count' => $this->lowStockQuery()->count(),
        ];

        return $this->success('Dashboard summary retrieved successfully.', $data);
    }

    public function recentTransactions(): JsonResponse
    {
        $transactions = Transaction::with(['transactionType', 'party'])
            ->latest()
            ->limit(10)
            ->get();

        return $this->success('Recent transactions retrieved successfully.', $transactions);
    }

    public function lowStockProducts(): JsonResponse
    {
        $products = $this->lowStockQuery()
            ->orderBy('products.name')
            ->get();

        return $this->success('Low stock products retrieved successfully.', $products);
    }

    public function topSellingProducts(): JsonResponse
    {
        $salesTypeId = $this->transactionTypeId('SALES_INVOICE');

        if (! $salesTypeId) {
            return $this->success('Top selling products retrieved successfully.', []);
        }

        $products = DB::table('transaction_lines')
            ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_lines.product_id', '=', 'products.id')
            ->where('transactions.transaction_type_id', $salesTypeId)
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.sku',
                'products.part_number',
            ])
            ->selectRaw('SUM(transaction_lines.quantity) as total_quantity_sold')
            ->selectRaw('SUM(transaction_lines.line_total) as total_sales_amount')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.part_number')
            ->orderByDesc('total_quantity_sold')
            ->limit(10)
            ->get();

        return $this->success('Top selling products retrieved successfully.', $products);
    }

    private function lowStockQuery()
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
            ->whereRaw('(COALESCE(stock.total_stock_in, 0) - COALESCE(stock.total_stock_out, 0)) <= products.reorder_level');
    }

    private function transactionTypeId(string $code): ?int
    {
        return DB::table('transaction_types')
            ->where('code', $code)
            ->value('id');
    }

    private function transactionSum(?int $transactionTypeId, string $column): float
    {
        if (! $transactionTypeId) {
            return 0;
        }

        return (float) Transaction::where('transaction_type_id', $transactionTypeId)->sum($column);
    }

    private function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
