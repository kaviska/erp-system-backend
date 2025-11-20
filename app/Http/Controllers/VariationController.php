<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use App\Models\Variation;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class VariationController extends Controller
{
    /**
     * Get all variations
     */
    #[OA\Get(
        path: "/api/variations",
        summary: "Get all variations",
        tags: ["Variations"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "product_id", in: "query", schema: new OA\Schema(type: "integer"), description: "Filter by product"),
        ],
        responses: [new OA\Response(response: 200, description: "Variations fetched successfully")]
    )]
    public function index(Request $request)
    {
        try {
            $query = Variation::with(['product', 'options']);

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Sort by display_order
            $query->orderBy('display_order', 'asc');

            $variations = $query->get();

            return Response::success($variations, 'Variations fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Get single variation
     */
    #[OA\Get(
        path: "/api/variations/{id}",
        summary: "Get single variation",
        tags: ["Variations"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        responses: [
            new OA\Response(response: 200, description: "Variation fetched successfully"),
            new OA\Response(response: 404, description: "Variation not found")
        ]
    )]
    public function show($id)
    {
        try {
            $variation = Variation::with(['product', 'options'])->find($id);

            if (!$variation) {
                return Response::error('', 'Variation not found', 404);
            }

            return Response::success($variation, 'Variation fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Create new variation
     */
    #[OA\Post(
        path: "/api/variations",
        summary: "Create new variation",
        tags: ["Variations"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["product_id", "name"],
                properties: [
                    new OA\Property(property: "product_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Color", maxLength: 255),
                    new OA\Property(property: "type", type: "string", nullable: true, example: "select"),
                    new OA\Property(property: "is_required", type: "boolean", example: true),
                    new OA\Property(property: "display_order", type: "integer", example: 1),
                    new OA\Property(property: "configuration", type: "object", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Variation created successfully"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'name' => 'required|string|max:255',
                'type' => 'nullable|string|max:50',
                'is_required' => 'nullable|boolean',
                'display_order' => 'nullable|integer|min:0',
                'configuration' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $variation = Variation::create([
                'product_id' => $request->product_id,
                'name' => $request->name,
                'type' => $request->type,
                'is_required' => $request->is_required ?? false,
                'display_order' => $request->display_order ?? 0,
                'configuration' => $request->configuration,
            ]);

            return Response::success($variation->load('product'), 'Variation created successfully', 201);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Update variation
     */
    #[OA\Put(
        path: "/api/variations/{id}",
        summary: "Update variation",
        tags: ["Variations"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent()),
        responses: [
            new OA\Response(response: 200, description: "Variation updated successfully"),
            new OA\Response(response: 404, description: "Variation not found"),
            new OA\Response(response: 422, description: "Validation failed")
        ]
    )]
    public function update(Request $request, $id)
    {
        try {
            $variation = Variation::find($id);

            if (!$variation) {
                return Response::error('', 'Variation not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'product_id' => 'sometimes|required|exists:products,id',
                'name' => 'sometimes|required|string|max:255',
                'type' => 'nullable|string|max:50',
                'is_required' => 'nullable|boolean',
                'display_order' => 'nullable|integer|min:0',
                'configuration' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $updateData = $request->only(['product_id', 'name', 'type', 'is_required', 'display_order', 'configuration']);

            $variation->update($updateData);

            return Response::success($variation->fresh()->load('product'), 'Variation updated successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Delete variation
     */
    #[OA\Delete(
        path: "/api/variations/{id}",
        summary: "Delete variation",
        tags: ["Variations"],
        security: [["bearerAuth" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer", example: 1))],
        responses: [
            new OA\Response(response: 200, description: "Variation deleted successfully"),
            new OA\Response(response: 404, description: "Variation not found"),
            new OA\Response(response: 400, description: "Cannot delete variation with existing options")
        ]
    )]
    public function destroy($id)
    {
        try {
            $variation = Variation::find($id);

            if (!$variation) {
                return Response::error('', 'Variation not found', 404);
            }

            // Check if variation has options
            if ($variation->options()->count() > 0) {
                return Response::error('', 'Cannot delete variation with existing options', 400);
            }

            $variation->delete();

            return Response::success('', 'Variation deleted successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
