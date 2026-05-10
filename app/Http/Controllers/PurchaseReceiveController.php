<?php

namespace App\Http\Controllers;

use App\Models\InventoryLedger;
use App\Models\Party;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Models\TransactionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseReceiveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $purchaseReceiveType = TransactionType::where('code', 'PURCHASE_RECEIVE')->first();

        if (! $purchaseReceiveType) {
            return $this->error('PURCHASE_RECEIVE transaction type was not found.', null, 422);
        }

        $purchaseReceives = Transaction::query()
            ->with(['transactionType', 'party', 'createdBy', 'transactionLines.product'])
            ->where('transaction_type_id', $purchaseReceiveType->id)
            ->latest()
            ->paginate($perPage);

        return $this->success('Purchase receives retrieved successfully.', $purchaseReceives);
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

        if (! in_array($party->party_type, ['supplier', 'both'], true)) {
            return $this->error('Party must be a supplier or both.', [
                'party_id' => ['The selected party is not allowed for purchase receive.'],
            ], 422);
        }

        $purchaseReceiveType = TransactionType::where('code', 'PURCHASE_RECEIVE')->first();

        if (! $purchaseReceiveType) {
            return $this->error('PURCHASE_RECEIVE transaction type was not found.', null, 422);
        }

        $transaction = DB::transaction(function () use ($data, $purchaseReceiveType) {
            $totalAmount = collect($data['items'])->sum(function (array $item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $discountAmount = $data['discount_amount'] ?? 0;
            $vatAmount = $data['vat_amount'] ?? 0;
            $paidAmount = $data['paid_amount'] ?? 0;
            $grandTotal = $totalAmount - $discountAmount + $vatAmount;
            $dueAmount = $grandTotal - $paidAmount;

            $transaction = Transaction::create([
                'transaction_type_id' => $purchaseReceiveType->id,
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
                    'stock_in' => $item['quantity'],
                    'stock_out' => 0,
                    'unit_cost' => $item['unit_price'],
                    'remarks' => 'Purchase receive: '.$data['invoice_no'],
                ]);
            }

            return $transaction;
        });

        $transaction->load(['transactionType', 'party', 'createdBy', 'transactionLines.product']);

        return $this->success('Purchase receive created successfully.', $transaction, 201);
    }

    public function show(int $id): JsonResponse
    {
        $purchaseReceiveType = TransactionType::where('code', 'PURCHASE_RECEIVE')->first();

        if (! $purchaseReceiveType) {
            return $this->error('PURCHASE_RECEIVE transaction type was not found.', null, 422);
        }

        $purchaseReceive = Transaction::with(['transactionType', 'party', 'createdBy', 'transactionLines.product'])
            ->where('transaction_type_id', $purchaseReceiveType->id)
            ->find($id);

        if (! $purchaseReceive) {
            return $this->error('Purchase receive not found.', null, 404);
        }

        return $this->success('Purchase receive retrieved successfully.', $purchaseReceive);
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
