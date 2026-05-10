<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $products = Product::query()
            ->with(['category', 'brand', 'uom'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%")
                        ->orWhere('part_number', 'ilike', "%{$search}%")
                        ->orWhere('model_no', 'ilike', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->query('category_id'));
            })
            ->when($request->filled('brand_id'), function ($query) use ($request) {
                $query->where('brand_id', $request->query('brand_id'));
            })
            ->when($request->filled('uom_id'), function ($query) use ($request) {
                $query->where('uom_id', $request->query('uom_id'));
            })
            ->when($request->filled('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            // Add low-stock filtering here when a reliable current stock value exists.
            ->latest()
            ->paginate($perPage);

        return $this->success('Products retrieved successfully.', $products);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors(), 422);
        }

        $product = Product::create($validator->validated());
        $product->load(['category', 'brand', 'uom']);

        return $this->success('Product created successfully.', $product, 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category', 'brand', 'uom'])->find($id);

        if (! $product) {
            return $this->error('Product not found.', null, 404);
        }

        return $this->success('Product retrieved successfully.', $product);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return $this->error('Product not found.', null, 404);
        }

        $validator = Validator::make($request->all(), $this->rules($product->id, true));

        if ($validator->fails()) {
            return $this->error('Validation failed.', $validator->errors(), 422);
        }

        $product->update($validator->validated());
        $product->load(['category', 'brand', 'uom']);

        return $this->success('Product updated successfully.', $product);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (! $product) {
            return $this->error('Product not found.', null, 404);
        }

        $product->delete();

        return $this->success('Product deleted successfully.');
    }

    private function rules(?int $productId = null, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes|required' : 'required';
        $nullable = $isUpdate ? 'sometimes|nullable' : 'nullable';

        return [
            'category_id' => [$required, 'integer', 'exists:product_categories,id'],
            'brand_id' => [$required, 'integer', 'exists:brands,id'],
            'uom_id' => [$required, 'integer', 'exists:uoms,id'],
            'name' => [$required, 'string', 'max:255'],
            'sku' => [
                $nullable,
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'slug' => [
                $nullable,
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'part_number' => [$nullable, 'string', 'max:255'],
            'model_no' => [$nullable, 'string', 'max:255'],
            'origin_country' => [$nullable, 'string', 'max:255'],
            'hs_code' => [$nullable, 'string', 'max:255'],
            'purchase_price' => [$nullable, 'numeric', 'min:0'],
            'sales_price' => [$nullable, 'numeric', 'min:0'],
            'reorder_level' => [$nullable, 'numeric', 'min:0'],
            'is_active' => [$isUpdate ? 'sometimes' : 'nullable', 'boolean'],
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
