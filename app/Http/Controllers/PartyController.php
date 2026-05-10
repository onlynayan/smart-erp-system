<?php

namespace App\Http\Controllers;

use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $parties = Party::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->when($request->filled('party_type'), function ($query) use ($request) {
                $query->where('party_type', $request->query('party_type'));
            })
            ->latest()
            ->paginate($perPage);

        return $this->success('Parties retrieved successfully.', $parties);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors(), 422);
        }

        $party = Party::create($validator->validated());

        return $this->success('Party created successfully.', $party, 201);
    }

    public function show(int $id): JsonResponse
    {
        $party = Party::find($id);

        if (! $party) {
            return $this->error('Party not found.', null, 404);
        }

        return $this->success('Party retrieved successfully.', $party);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $party = Party::find($id);

        if (! $party) {
            return $this->error('Party not found.', null, 404);
        }

        $validator = Validator::make($request->all(), $this->rules(true));

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors(), 422);
        }

        $party->update($validator->validated());

        return $this->success('Party updated successfully.', $party);
    }

    public function destroy(int $id): JsonResponse
    {
        $party = Party::find($id);

        if (! $party) {
            return $this->error('Party not found.', null, 404);
        }

        $party->delete();

        return $this->success('Party deleted successfully.');
    }

    private function rules(bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';
        $nullable = $isUpdate ? 'sometimes|nullable' : 'nullable';

        return [
            'party_type' => [$required, Rule::in(['customer', 'supplier', 'both'])],
            'name' => [$required, 'string', 'max:255'],
            'phone' => [$nullable, 'string', 'max:50'],
            'email' => [$nullable, 'email', 'max:255'],
            'address' => [$nullable, 'string'],
        ];
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
