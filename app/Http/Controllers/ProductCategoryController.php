<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return $this->success('Categories retrieved successfully.', $categories);
    }

    public function children(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        $children = $category->children()
            ->orderBy('name')
            ->get();

        return $this->success('Category children retrieved successfully.', $children);
    }

    public function products(Request $request, int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $products = $category->products()
            ->with(['category', 'brand', 'uom'])
            ->latest()
            ->paginate($perPage);

        return $this->success('Category products retrieved successfully.', $products);
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
