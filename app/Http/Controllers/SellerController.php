<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use App\Models\Seller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class SellerController extends Controller
{
    /**
     * Get all sellers
     */
    #[OA\Get(
        path: "/api/sellers",
        summary: "Get all sellers",
        tags: ["Sellers"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "status", in: "query", schema: new OA\Schema(type: "string", enum: ["active", "inactive", "pending"])),
            new OA\Parameter(name: "search", in: "query", schema: new OA\Schema(type: "string"), description: "Search by name, email, or shop name"),
        ],
        responses: [new OA\Response(response: 200, description: "Sellers fetched successfully")]
    )]
    public function index(Request $request)
    {
        try {
            $query = Seller::withCount('products');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search by name, email, or shop_name
            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%')
                        ->orWhere('shop_name', 'like', '%' . $request->search . '%');
                });
            }

            $sellers = $query->get();

            return Response::success($sellers, 'Sellers fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Get single seller
     */
    #[OA\Get(
        path: "/api/sellers/{id}",
        summary: "Get single seller",
        tags: ["Sellers"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        responses: [
            new OA\Response(response: 200, description: "Seller fetched successfully"),
            new OA\Response(response: 404, description: "Seller not found")
        ]
    )]
    public function show($id)
    {
        try {
            $seller = Seller::with('products')->find($id);

            if (!$seller) {
                return Response::error('', 'Seller not found', 404);
            }

            return Response::success($seller, 'Seller fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Create new seller
     */
    #[OA\Post(
        path: "/api/sellers",
        summary: "Create new seller",
        tags: ["Sellers"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "shop_name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe", maxLength: 255),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "phone", type: "string", nullable: true, example: "+1234567890"),
                    new OA\Property(property: "shop_name", type: "string", example: "John's Shop", maxLength: 255),
                    new OA\Property(property: "address", type: "string", nullable: true),
                    new OA\Property(property: "city", type: "string", nullable: true, example: "New York"),
                    new OA\Property(property: "country", type: "string", nullable: true, example: "USA"),
                    new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "pending"], example: "pending"),
                    new OA\Property(property: "rating", type: "number", format: "float", example: 4.5, minimum: 0, maximum: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Seller created successfully"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:sellers,email',
                'phone' => 'nullable|string|max:20',
                'shop_name' => 'required|string|max:255',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'status' => 'nullable|string|in:active,inactive,pending',
                'rating' => 'nullable|numeric|min:0|max:5',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $nameSlug = Str::slug($request->name);
            $shopSlug = Str::slug($request->shop_name);
            $uniqueNameSlug = $nameSlug;
            $uniqueShopSlug = $shopSlug;
            $counter = 1;

            // Ensure unique slugs
            while (Seller::where('slug', $uniqueNameSlug)->exists()) {
                $uniqueNameSlug = $nameSlug . '-' . $counter;
                $counter++;
            }

            $counter = 1;
            while (Seller::where('shop_slug', $uniqueShopSlug)->exists()) {
                $uniqueShopSlug = $shopSlug . '-' . $counter;
                $counter++;
            }

            $seller = Seller::create([
                'name' => $request->name,
                'slug' => $uniqueNameSlug,
                'email' => $request->email,
                'phone' => $request->phone,
                'shop_name' => $request->shop_name,
                'shop_slug' => $uniqueShopSlug,
                'address' => $request->address,
                'city' => $request->city,
                'country' => $request->country,
                'status' => $request->status ?? 'pending',
                'rating' => $request->rating ?? 0,
                'metadata' => $request->metadata,
            ]);

            return Response::success($seller, 'Seller created successfully', 201);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Update seller
     */
    #[OA\Put(
        path: "/api/sellers/{id}",
        summary: "Update seller",
        tags: ["Sellers"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent()),
        responses: [
            new OA\Response(response: 200, description: "Seller updated successfully"),
            new OA\Response(response: 404, description: "Seller not found"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function update(Request $request, $id)
    {
        try {
            $seller = Seller::find($id);

            if (!$seller) {
                return Response::error('', 'Seller not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:sellers,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'shop_name' => 'sometimes|required|string|max:255',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'status' => 'nullable|string|in:active,inactive,pending',
                'rating' => 'nullable|numeric|min:0|max:5',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $updateData = $request->only(['name', 'email', 'phone', 'shop_name', 'address', 'city', 'country', 'status', 'rating', 'metadata']);

            // Update slug if name changed
            if ($request->has('name') && $request->name !== $seller->name) {
                $slug = Str::slug($request->name);
                $uniqueSlug = $slug;
                $counter = 1;

                while (Seller::where('slug', $uniqueSlug)->where('id', '!=', $id)->exists()) {
                    $uniqueSlug = $slug . '-' . $counter;
                    $counter++;
                }

                $updateData['slug'] = $uniqueSlug;
            }

            // Update shop_slug if shop_name changed
            if ($request->has('shop_name') && $request->shop_name !== $seller->shop_name) {
                $shopSlug = Str::slug($request->shop_name);
                $uniqueShopSlug = $shopSlug;
                $counter = 1;

                while (Seller::where('shop_slug', $uniqueShopSlug)->where('id', '!=', $id)->exists()) {
                    $uniqueShopSlug = $shopSlug . '-' . $counter;
                    $counter++;
                }

                $updateData['shop_slug'] = $uniqueShopSlug;
            }

            $seller->update($updateData);

            return Response::success($seller->fresh(), 'Seller updated successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Delete seller
     */
    #[OA\Delete(
        path: "/api/sellers/{id}",
        summary: "Delete seller",
        tags: ["Sellers"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        responses: [
            new OA\Response(response: 200, description: "Seller deleted successfully"),
            new OA\Response(response: 404, description: "Seller not found"),
            new OA\Response(response: 400, description: "Cannot delete seller with existing products")
        ]
    )]
    public function destroy($id)
    {
        try {
            $seller = Seller::find($id);

            if (!$seller) {
                return Response::error('', 'Seller not found', 404);
            }

            // Check if seller has products
            if ($seller->products()->count() > 0) {
                return Response::error('', 'Cannot delete seller with existing products', 400);
            }

            $seller->delete();

            return Response::success('', 'Seller deleted successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
