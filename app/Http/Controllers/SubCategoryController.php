<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use App\Models\SubCategory;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class SubCategoryController extends Controller
{
    /**
     * Get all sub categories
     */
    #[OA\Get(
        path: "/api/sub-categories",
        summary: "Get all sub categories",
        tags: ["Sub Categories"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "category_id", in: "query", schema: new OA\Schema(type: "integer"), description: "Filter by category"),
            new OA\Parameter(name: "is_active", in: "query", schema: new OA\Schema(type: "boolean"), description: "Filter by active status"),
            new OA\Parameter(name: "search", in: "query", schema: new OA\Schema(type: "string"), description: "Search by name"),
        ],
        responses: [new OA\Response(response: 200, description: "Sub categories fetched successfully")]
    )]
    public function index(Request $request)
    {
        try {
            $query = SubCategory::with('category');

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

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

            $subCategories = $query->get();

            return Response::success($subCategories, 'Sub categories fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Get single sub category
     */
    #[OA\Get(
        path: "/api/sub-categories/{id}",
        summary: "Get single sub category",
        tags: ["Sub Categories"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        responses: [
            new OA\Response(response: 200, description: "Sub category fetched successfully"),
            new OA\Response(response: 404, description: "Sub category not found")
        ]
    )]
    public function show($id)
    {
        try {
            $subCategory = SubCategory::with('category', 'products')->find($id);

            if (!$subCategory) {
                return Response::error('', 'Sub category not found', 404);
            }

            return Response::success($subCategory, 'Sub category fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Create new sub category
     */
    #[OA\Post(
        path: "/api/sub-categories",
        summary: "Create new sub category",
        tags: ["Sub Categories"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_id", "name"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Laptops", maxLength: 255),
                    new OA\Property(property: "image_path", type: "string", nullable: true, example: "sub-categories/laptops.jpg"),
                    new OA\Property(property: "description", type: "string", nullable: true),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "display_order", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Sub category created successfully"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
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
            while (SubCategory::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $slug . '-' . $counter;
                $counter++;
            }

            $subCategory = SubCategory::create([
                'category_id' => $request->category_id,
                'name' => $request->name,
                'slug' => $uniqueSlug,
                'image_path' => $request->image_path,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'display_order' => $request->display_order ?? 0,
            ]);

            return Response::success($subCategory->load('category'), 'Sub category created successfully', 201);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Update sub category
     */
    #[OA\Put(
        path: "/api/sub-categories/{id}",
        summary: "Update sub category",
        tags: ["Sub Categories"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent()),
        responses: [
            new OA\Response(response: 200, description: "Sub category updated successfully"),
            new OA\Response(response: 404, description: "Sub category not found"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function update(Request $request, $id)
    {
        try {
            $subCategory = SubCategory::find($id);

            if (!$subCategory) {
                return Response::error('', 'Sub category not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'category_id' => 'sometimes|required|exists:categories,id',
                'name' => 'sometimes|required|string|max:255',
                'image_path' => 'nullable|string|max:500',
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'display_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $updateData = $request->only(['category_id', 'name', 'image_path', 'description', 'is_active', 'display_order']);

            // Update slug if name changed
            if ($request->has('name') && $request->name !== $subCategory->name) {
                $slug = Str::slug($request->name);
                $uniqueSlug = $slug;
                $counter = 1;

                while (SubCategory::where('slug', $uniqueSlug)->where('id', '!=', $id)->exists()) {
                    $uniqueSlug = $slug . '-' . $counter;
                    $counter++;
                }

                $updateData['slug'] = $uniqueSlug;
            }

            $subCategory->update($updateData);

            return Response::success($subCategory->fresh()->load('category'), 'Sub category updated successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Delete sub category
     */
    #[OA\Delete(
        path: "/api/sub-categories/{id}",
        summary: "Delete sub category",
        tags: ["Sub Categories"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        responses: [
            new OA\Response(response: 200, description: "Sub category deleted successfully"),
            new OA\Response(response: 404, description: "Sub category not found"),
            new OA\Response(response: 400, description: "Cannot delete sub category with existing products")
        ]
    )]
    public function destroy($id)
    {
        try {
            $subCategory = SubCategory::find($id);

            if (!$subCategory) {
                return Response::error('', 'Sub category not found', 404);
            }

            // Check if sub category has products
            if ($subCategory->products()->count() > 0) {
                return Response::error('', 'Cannot delete sub category with existing products', 400);
            }

            $subCategory->delete();

            return Response::success('', 'Sub category deleted successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
