<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    /**
     * Get all categories
     */
    #[OA\Get(
        path: "/api/categories",
        summary: "Get all categories",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "is_active",
                in: "query",
                description: "Filter by active status",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Search categories by name",
                required: false,
                schema: new OA\Schema(type: "string", example: "Electronics")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categories fetched successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Categories fetched successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer", example: 1),
                                    new OA\Property(property: "name", type: "string", example: "Electronics"),
                                    new OA\Property(property: "slug", type: "string", example: "electronics"),
                                    new OA\Property(property: "image_path", type: "string", nullable: true, example: "categories/electronics.jpg"),
                                    new OA\Property(property: "description", type: "string", nullable: true, example: "Electronic products and gadgets"),
                                    new OA\Property(property: "is_active", type: "boolean", example: true),
                                    new OA\Property(property: "display_order", type: "integer", example: 1),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time"),
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        try {
            $query = Category::with('subCategories');

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active === 'true' || $request->is_active === '1');
            }

            // Search by name
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Sort by display_order
            $query->orderBy('display_order', 'asc');

            $categories = $query->get();

            return Response::success($categories, 'Categories fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Get single category
     */
    #[OA\Get(
        path: "/api/categories/{id}",
        summary: "Get single category",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Category ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Category fetched successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Category fetched successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Electronics"),
                                new OA\Property(property: "slug", type: "string", example: "electronics"),
                                new OA\Property(property: "image_path", type: "string", nullable: true, example: "categories/electronics.jpg"),
                                new OA\Property(property: "description", type: "string", nullable: true),
                                new OA\Property(property: "is_active", type: "boolean", example: true),
                                new OA\Property(property: "display_order", type: "integer", example: 1),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    public function show($id)
    {
        try {
            $category = Category::with('subCategories', 'products')->find($id);

            if (!$category) {
                return Response::error('', 'Category not found', 404);
            }

            return Response::success($category, 'Category fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Create new category
     */
    #[OA\Post(
        path: "/api/categories",
        summary: "Create new category",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Electronics", maxLength: 255),
                    new OA\Property(property: "image_path", type: "string", nullable: true, example: "categories/electronics.jpg", maxLength: 500),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Electronic products and gadgets"),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "display_order", type: "integer", example: 1, minimum: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Category created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Category created successfully"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "name", type: "string", example: "Electronics"),
                                new OA\Property(property: "slug", type: "string", example: "electronics"),
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'image_path' => 'nullable|string|max:500',
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'display_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $slug = Str::slug($request->name);
            $uniqueSlug = $slug;
            $counter = 1;

            // Ensure unique slug
            while (Category::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $slug . '-' . $counter;
                $counter++;
            }

            $category = Category::create([
                'name' => $request->name,
                'slug' => $uniqueSlug,
                'image_path' => $request->image_path,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'display_order' => $request->display_order ?? 0,
            ]);

            return Response::success($category, 'Category created successfully', 201);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Update category
     */
    #[OA\Put(
        path: "/api/categories/{id}",
        summary: "Update category",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Category ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Electronics Updated", maxLength: 255),
                    new OA\Property(property: "image_path", type: "string", nullable: true, example: "categories/electronics-updated.jpg"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "display_order", type: "integer", example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Category updated successfully"),
            new OA\Response(response: 404, description: "Category not found"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return Response::error('', 'Category not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'image_path' => 'nullable|string|max:500',
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'display_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $updateData = $request->only(['name', 'image_path', 'description', 'is_active', 'display_order']);

            // Update slug if name changed
            if ($request->has('name') && $request->name !== $category->name) {
                $slug = Str::slug($request->name);
                $uniqueSlug = $slug;
                $counter = 1;

                while (Category::where('slug', $uniqueSlug)->where('id', '!=', $id)->exists()) {
                    $uniqueSlug = $slug . '-' . $counter;
                    $counter++;
                }

                $updateData['slug'] = $uniqueSlug;
            }

            $category->update($updateData);

            return Response::success($category->fresh(), 'Category updated successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Delete category
     */
    #[OA\Delete(
        path: "/api/categories/{id}",
        summary: "Delete category",
        tags: ["Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Category ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Category deleted successfully"),
            new OA\Response(response: 404, description: "Category not found"),
            new OA\Response(response: 400, description: "Cannot delete category with existing products")
        ]
    )]
    public function destroy($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return Response::error('', 'Category not found', 404);
            }

            // Check if category has products
            if ($category->products()->count() > 0) {
                return Response::error('', 'Cannot delete category with existing products', 400);
            }

            $category->delete();

            return Response::success('', 'Category deleted successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
