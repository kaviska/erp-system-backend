<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    /**
     * Get all products
     */
    #[OA\Get(
        path: "/api/products",
        summary: "Get all products",
        tags: ["Products"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "seller_id", in: "query", schema: new OA\Schema(type: "integer"), description: "Filter by seller"),
            new OA\Parameter(name: "category_id", in: "query", schema: new OA\Schema(type: "integer"), description: "Filter by category"),
            new OA\Parameter(name: "sub_category_id", in: "query", schema: new OA\Schema(type: "integer"), description: "Filter by sub category"),
            new OA\Parameter(name: "status", in: "query", schema: new OA\Schema(type: "string", enum: ["draft", "active", "archived"]), description: "Filter by status"),
            new OA\Parameter(name: "is_published", in: "query", schema: new OA\Schema(type: "boolean"), description: "Filter by published status"),
            new OA\Parameter(name: "search", in: "query", schema: new OA\Schema(type: "string"), description: "Search by name, SKU, or brand"),
            new OA\Parameter(name: "min_price", in: "query", schema: new OA\Schema(type: "number"), description: "Minimum price"),
            new OA\Parameter(name: "max_price", in: "query", schema: new OA\Schema(type: "number"), description: "Maximum price"),
            new OA\Parameter(name: "per_page", in: "query", schema: new OA\Schema(type: "integer"), description: "Items per page", example: 15),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Products fetched successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Products fetched successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(
                                    property: "data",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "id", type: "integer", example: 1),
                                            new OA\Property(property: "name", type: "string", example: "Wireless Headphones"),
                                            new OA\Property(property: "sku", type: "string", example: "WH-001"),
                                            new OA\Property(property: "price", type: "number", format: "float", example: 99.99),
                                            new OA\Property(property: "sale_price", type: "number", format: "float", nullable: true, example: 79.99),
                                            new OA\Property(property: "stock_quantity", type: "integer", example: 50),
                                            new OA\Property(property: "status", type: "string", example: "active"),
                                        ]
                                    )
                                )
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        try {
            $query = Product::with(['seller', 'category', 'subCategory', 'variations', 'variationStocks']);

            // Filter by seller
            if ($request->has('seller_id')) {
                $query->where('seller_id', $request->seller_id);
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by sub category
            if ($request->has('sub_category_id')) {
                $query->where('sub_category_id', $request->sub_category_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by published status
            if ($request->has('is_published')) {
                $query->where('is_published', $request->is_published === 'true' || $request->is_published === '1');
            }

            // Search by name, SKU, or brand
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('sku', 'like', '%' . $request->search . '%')
                        ->orWhere('brand', 'like', '%' . $request->search . '%');
                });
            }

            // Price range filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            $products = $query->paginate($request->per_page ?? 15);

            return Response::success($products, 'Products fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Get single product
     */
    #[OA\Get(
        path: "/api/products/{id}",
        summary: "Get single product",
        tags: ["Products"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Product fetched successfully"),
            new OA\Response(response: 404, description: "Product not found")
        ]
    )]
    public function show($id)
    {
        try {
            $product = Product::with([
                'seller',
                'category',
                'subCategory',
                'variations.options',
                'variationStocks.variationOptions'
            ])->find($id);

            if (!$product) {
                return Response::error('', 'Product not found', 404);
            }

            return Response::success($product, 'Product fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Create new product
     */
    #[OA\Post(
        path: "/api/products",
        summary: "Create new product",
        tags: ["Products"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["seller_id", "category_id", "name", "sku", "price"],
                properties: [
                    new OA\Property(property: "seller_id", type: "integer", example: 1),
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "sub_category_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "name", type: "string", example: "Wireless Headphones", maxLength: 255),
                    new OA\Property(property: "sku", type: "string", example: "WH-001", maxLength: 255),
                    new OA\Property(property: "image_path", type: "string", nullable: true, example: "products/headphones.jpg"),
                    new OA\Property(property: "barcode", type: "string", nullable: true, example: "1234567890123"),
                    new OA\Property(property: "type", type: "string", enum: ["physical", "digital"], example: "physical"),
                    new OA\Property(property: "brand", type: "string", nullable: true, example: "TechBrand"),
                    new OA\Property(property: "short_description", type: "string", nullable: true, example: "High-quality wireless headphones"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Premium wireless headphones with noise cancellation"),
                    new OA\Property(property: "lead_time", type: "integer", example: 2, minimum: 0),
                    new OA\Property(property: "price", type: "number", format: "float", example: 99.99, minimum: 0),
                    new OA\Property(property: "sale_price", type: "number", format: "float", nullable: true, example: 79.99),
                    new OA\Property(property: "currency", type: "string", example: "USD", maxLength: 3),
                    new OA\Property(property: "stock_quantity", type: "integer", example: 50, minimum: 0),
                    new OA\Property(property: "track_inventory", type: "boolean", example: true),
                    new OA\Property(property: "is_published", type: "boolean", example: false),
                    new OA\Property(property: "status", type: "string", enum: ["draft", "active", "archived"], example: "draft"),
                    new OA\Property(property: "metadata", type: "object", nullable: true, example: ["tags" => ["electronics", "audio"]]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Product created successfully"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'seller_id' => 'required|exists:sellers,id',
                'category_id' => 'required|exists:categories,id',
                'sub_category_id' => 'nullable|exists:sub_categories,id',
                'name' => 'required|string|max:255',
                'sku' => 'required|string|unique:products,sku',
                'image_path' => 'nullable|string|max:500',
                'barcode' => 'nullable|string|max:100',
                'type' => 'nullable|string|in:physical,digital',
                'brand' => 'nullable|string|max:255',
                'short_description' => 'nullable|string',
                'description' => 'nullable|string',
                'lead_time' => 'nullable|integer|min:0',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'stock_quantity' => 'nullable|integer|min:0',
                'track_inventory' => 'nullable|boolean',
                'is_published' => 'nullable|boolean',
                'status' => 'nullable|string|in:draft,active,archived',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $slug = Str::slug($request->name);
            $uniqueSlug = $slug;
            $counter = 1;

            // Ensure unique slug
            while (Product::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $slug . '-' . $counter;
                $counter++;
            }

            $product = Product::create([
                'seller_id' => $request->seller_id,
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'name' => $request->name,
                'slug' => $uniqueSlug,
                'sku' => $request->sku,
                'image_path' => $request->image_path,
                'barcode' => $request->barcode,
                'type' => $request->type ?? 'physical',
                'brand' => $request->brand,
                'short_description' => $request->short_description,
                'description' => $request->description,
                'lead_time' => $request->lead_time ?? 0,
                'price' => $request->price,
                'sale_price' => $request->sale_price,
                'currency' => $request->currency ?? 'USD',
                'stock_quantity' => $request->stock_quantity ?? 0,
                'track_inventory' => $request->track_inventory ?? true,
                'is_published' => $request->is_published ?? false,
                'published_at' => $request->is_published ? now() : null,
                'status' => $request->status ?? 'draft',
                'metadata' => $request->metadata,
            ]);

            return Response::success($product->load(['seller', 'category', 'subCategory']), 'Product created successfully', 201);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Update product
     */
    #[OA\Put(
        path: "/api/products/{id}",
        summary: "Update product",
        tags: ["Products"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Wireless Headphones Pro"),
                    new OA\Property(property: "price", type: "number", format: "float", example: 129.99),
                    new OA\Property(property: "status", type: "string", enum: ["draft", "active", "archived"], example: "active"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Product updated successfully"),
            new OA\Response(response: 404, description: "Product not found"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return Response::error('', 'Product not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'seller_id' => 'sometimes|required|exists:sellers,id',
                'category_id' => 'sometimes|required|exists:categories,id',
                'sub_category_id' => 'nullable|exists:sub_categories,id',
                'name' => 'sometimes|required|string|max:255',
                'sku' => 'sometimes|required|string|unique:products,sku,' . $id,
                'image_path' => 'nullable|string|max:500',
                'barcode' => 'nullable|string|max:100',
                'type' => 'nullable|string|in:physical,digital',
                'brand' => 'nullable|string|max:255',
                'short_description' => 'nullable|string',
                'description' => 'nullable|string',
                'lead_time' => 'nullable|integer|min:0',
                'price' => 'sometimes|required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'stock_quantity' => 'nullable|integer|min:0',
                'track_inventory' => 'nullable|boolean',
                'is_published' => 'nullable|boolean',
                'status' => 'nullable|string|in:draft,active,archived',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $updateData = $request->only([
                'seller_id', 'category_id', 'sub_category_id', 'name', 'sku', 'image_path',
                'barcode', 'type', 'brand', 'short_description', 'description', 'lead_time',
                'price', 'sale_price', 'currency', 'stock_quantity', 'track_inventory',
                'is_published', 'status', 'metadata'
            ]);

            // Update slug if name changed
            if ($request->has('name') && $request->name !== $product->name) {
                $slug = Str::slug($request->name);
                $uniqueSlug = $slug;
                $counter = 1;

                while (Product::where('slug', $uniqueSlug)->where('id', '!=', $id)->exists()) {
                    $uniqueSlug = $slug . '-' . $counter;
                    $counter++;
                }

                $updateData['slug'] = $uniqueSlug;
            }

            // Update published_at if is_published changed
            if ($request->has('is_published')) {
                if ($request->is_published && !$product->is_published) {
                    $updateData['published_at'] = now();
                } elseif (!$request->is_published) {
                    $updateData['published_at'] = null;
                }
            }

            $product->update($updateData);

            return Response::success($product->fresh()->load(['seller', 'category', 'subCategory']), 'Product updated successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Delete product
     */
    #[OA\Delete(
        path: "/api/products/{id}",
        summary: "Delete product",
        tags: ["Products"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Product deleted successfully"),
            new OA\Response(response: 404, description: "Product not found")
        ]
    )]
    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return Response::error('', 'Product not found', 404);
            }

            $product->delete();

            return Response::success('', 'Product deleted successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
