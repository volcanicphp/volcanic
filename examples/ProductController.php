<?php

declare(strict_types=1);

namespace Examples;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Volcanic\Attributes\Route;

class ProductController extends Controller
{
    #[Route(
        methods: ['GET'],
        uri: '/api/products',
        name: 'products.index',
        middleware: ['auth:api']
    )]
    public function index(Request $request): JsonResponse
    {
        // Your index logic here - fetch products, apply filters, etc.
        $products = collect([
            ['id' => 1, 'name' => 'Product 1', 'price' => 99.99],
            ['id' => 2, 'name' => 'Product 2', 'price' => 149.99],
        ]);

        return response()->json([
            'data' => $products,
            'message' => 'Products retrieved successfully',
        ]);
    }

    #[Route(
        methods: ['GET'],
        uri: '/api/products/{id}',
        name: 'products.show',
        middleware: ['auth:api'],
        where: ['id' => '[0-9]+']
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        // Your show logic here - find specific product
        $product = [
            'id' => $id,
            'name' => 'Product '.$id,
            'price' => 99.99,
            'description' => 'A great product',
        ];

        return response()->json([
            'data' => $product,
            'message' => 'Product retrieved successfully',
        ]);
    }

    #[Route(
        methods: ['POST'],
        uri: '/api/products',
        name: 'products.store',
        middleware: ['auth:api']
    )]
    public function store(Request $request): JsonResponse
    {
        // Your store logic here - validate request, create product, etc.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Create product logic would go here
        $product = [
            'id' => rand(1, 1000),
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'created_at' => now(),
        ];

        return response()->json([
            'data' => $product,
            'message' => 'Product created successfully',
        ], 201);
    }

    #[Route(
        methods: ['PUT', 'PATCH'],
        uri: '/api/products/{id}',
        name: 'products.update',
        middleware: ['auth:api'],
        where: ['id' => '[0-9]+']
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        // Your update logic here - validate request, update product, etc.
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Update product logic would go here
        $product = [
            'id' => $id,
            'name' => $validated['name'] ?? 'Product '.$id,
            'price' => $validated['price'] ?? 99.99,
            'description' => $validated['description'] ?? null,
            'updated_at' => now(),
        ];

        return response()->json([
            'data' => $product,
            'message' => 'Product updated successfully',
        ]);
    }

    #[Route(
        methods: ['DELETE'],
        uri: '/api/products/{id}',
        name: 'products.destroy',
        middleware: ['auth:api'],
        where: ['id' => '[0-9]+']
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Your destroy logic here - soft delete, hard delete, etc.
        // Example: Product::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    #[Route(
        methods: ['PATCH'],
        uri: '/api/products/{id}/restore',
        name: 'products.restore',
        middleware: ['auth:api'],
        where: ['id' => '[0-9]+']
    )]
    public function restore(Request $request, int $id): JsonResponse
    {
        // Your restore logic here for soft-deleted products
        // Example: Product::withTrashed()->findOrFail($id)->restore();

        return response()->json([
            'message' => 'Product restored successfully',
        ]);
    }

    #[Route(
        methods: ['DELETE'],
        uri: '/api/products/{id}/force',
        name: 'products.forceDelete',
        middleware: ['auth:api', 'can:force-delete-products'],
        where: ['id' => '[0-9]+']
    )]
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        // Your force delete logic here - permanently delete
        // Example: Product::withTrashed()->findOrFail($id)->forceDelete();

        return response()->json([
            'message' => 'Product permanently deleted',
        ]);
    }

    // Example without specifying URI - will auto-generate
    #[Route(
        methods: ['GET'],
        middleware: ['auth:api']
    )]
    public function featured(Request $request): JsonResponse
    {
        // This will automatically generate:
        // URI: /product/featured
        // Name: product.featured

        return response()->json([
            'data' => [],
            'message' => 'Featured products retrieved',
        ]);
    }

    // Example with domain constraint
    #[Route(
        methods: ['GET'],
        uri: '/api/admin/products/stats',
        name: 'admin.products.stats',
        middleware: ['auth:api', 'role:admin'],
        domain: 'admin.example.com'
    )]
    public function adminStats(Request $request): JsonResponse
    {
        return response()->json([
            'total_products' => 150,
            'active_products' => 120,
            'inactive_products' => 30,
        ]);
    }
}
