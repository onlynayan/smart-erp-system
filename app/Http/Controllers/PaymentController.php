<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $payments = Payment::query()
            ->with(['transaction', 'party', 'createdBy'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where('reference_no', 'ilike', "%{$search}%");
            })
            ->when($request->filled('party_id'), function ($query) use ($request) {
                $query->where('party_id', $request->query('party_id'));
            })
            ->when($request->filled('payment_type'), function ($query) use ($request) {
                $query->where('payment_type', $request->query('payment_type'));
            })
            ->when($request->filled('payment_date'), function ($query) use ($request) {
                $query->whereDate('payment_date', $request->query('payment_date'));
            })
            ->latest()
            ->paginate($perPage);

        return $this->success('Payments retrieved successfully.', $payments);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'payment_date' => ['required', 'date'],
            'payment_type' => ['required', Rule::in(['RECEIVE', 'PAY'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors(), 422);
        }

        $data = $validator->validated();

        $payment = DB::transaction(function () use ($data) {
            $transaction = Transaction::lockForUpdate()->find($data['transaction_id']);

            $payment = Payment::create([
                'transaction_id' => $transaction->id,
                'party_id' => $data['party_id'],
                'payment_date' => $data['payment_date'],
                'payment_type' => $data['payment_type'],
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $paidAmount = $transaction->paid_amount + $data['amount'];
            $dueAmount = $transaction->grand_total - $paidAmount;

            $transaction->update([
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
            ]);

            return $payment;
        });

        $payment->load(['transaction', 'party', 'createdBy']);

        return $this->success('Payment created successfully.', $payment, 201);
    }

    public function show(int $id): JsonResponse
    {
        $payment = Payment::with(['transaction', 'party', 'createdBy'])->find($id);

        if (! $payment) {
            return $this->error('Payment not found.', null, 404);
        }

        return $this->success('Payment retrieved successfully.', $payment);
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
