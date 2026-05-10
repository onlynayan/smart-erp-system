<?php

namespace App\Http\Controllers;

use App\Models\InventoryLedger;
use App\Models\Party;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Models\TransactionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalesInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $salesInvoiceType = TransactionType::where('code', 'SALES_INVOICE')->first();

        if (! $salesInvoiceType) {
            return $this->error('SALES_INVOICE transaction type was not found.', null, 422);
        }

        $salesInvoices = Transaction::query()
            ->with(['transactionType', 'party', 'createdBy', 'transactionLines.product'])
            ->where('transaction_type_id', $salesInvoiceType->id)
            ->latest()
            ->paginate($perPage);

        return $this->success('Sales invoices retrieved successfully.', $salesInvoices);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'transaction_date' => ['required', 'date'],
            'invoice_no' => ['required', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors(), 422);
        }

        $data = $validator->validated();
        $party = Party::find($data['party_id']);

        if (! in_array($party->party_type, ['customer', 'both'], true)) {
            return $this->error('Party must be a customer or both.', [
                'party_id' => ['The selected party is not allowed for sales invoice.'],
            ], 422);
        }

        $salesInvoiceType = TransactionType::where('code', 'SALES_INVOICE')->first();

        if (! $salesInvoiceType) {
            return $this->error('SALES_INVOICE transaction type was not found.', null, 422);
        }

        $stockErrors = $this->validateStock($data['items']);

        if (! empty($stockErrors)) {
            return $this->error('Insufficient stock.', $stockErrors, 422);
        }

        $transaction = DB::transaction(function () use ($data, $salesInvoiceType) {
            $totalAmount = collect($data['items'])->sum(function (array $item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $discountAmount = $data['discount_amount'] ?? 0;
            $vatAmount = $data['vat_amount'] ?? 0;
            $paidAmount = $data['paid_amount'] ?? 0;
            $grandTotal = $totalAmount - $discountAmount + $vatAmount;
            $dueAmount = $grandTotal - $paidAmount;

            $transaction = Transaction::create([
                'transaction_type_id' => $salesInvoiceType->id,
                'party_id' => $data['party_id'],
                'transaction_date' => $data['transaction_date'],
                'invoice_no' => $data['invoice_no'],
                'status' => 'posted',
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];

                $line = TransactionLine::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);

                InventoryLedger::create([
                    'transaction_id' => $transaction->id,
                    'transaction_line_id' => $line->id,
                    'product_id' => $item['product_id'],
                    'transaction_date' => $data['transaction_date'],
                    'stock_in' => 0,
                    'stock_out' => $item['quantity'],
                    'unit_cost' => $item['unit_price'],
                    'remarks' => 'Sales invoice: '.$data['invoice_no'],
                ]);
            }

            return $transaction;
        });

        $transaction->load(['transactionType', 'party', 'createdBy', 'transactionLines.product']);

        return $this->success('Sales invoice created successfully.', $transaction, 201);
    }

    public function show(int $id): JsonResponse
    {
        $salesInvoiceType = TransactionType::where('code', 'SALES_INVOICE')->first();

        if (! $salesInvoiceType) {
            return $this->error('SALES_INVOICE transaction type was not found.', null, 422);
        }

        $salesInvoice = Transaction::with(['transactionType', 'party', 'createdBy', 'transactionLines.product'])
            ->where('transaction_type_id', $salesInvoiceType->id)
            ->find($id);

        if (! $salesInvoice) {
            return $this->error('Sales invoice not found.', null, 404);
        }

        return $this->success('Sales invoice retrieved successfully.', $salesInvoice);
    }

    private function validateStock(array $items): array
    {
        $requestedItems = collect($items)
            ->groupBy('product_id')
            ->map(function ($items) {
                return $items->sum('quantity');
            });

        $stockErrors = [];

        foreach ($requestedItems as $productId => $requestedQuantity) {
            $product = Product::find($productId);
            $currentStock = InventoryLedger::where('product_id', $productId)
                ->selectRaw('COALESCE(SUM(stock_in), 0) - COALESCE(SUM(stock_out), 0) as current_stock')
                ->value('current_stock') ?? 0;

            if ($requestedQuantity > $currentStock) {
                $stockErrors['items'][] = sprintf(
                    '%s has only %s in stock. Requested quantity is %s.',
                    $product->name,
                    $currentStock,
                    $requestedQuantity
                );
            }
        }

        return $stockErrors;
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
