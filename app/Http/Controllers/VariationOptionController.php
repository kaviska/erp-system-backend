<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use App\Models\VariationOption;
use App\Models\Variation;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class VariationOptionController extends Controller
{
    /**
     * Get all variation options
     */
    #[OA\Get(
        path: "/api/variation-options",
        summary: "Get all variation options",
        tags: ["Variation Options"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "variation_id", in: "query", schema: new OA\Schema(type: "integer"), description: "Filter by variation"),
        ],
        responses: [new OA\Response(response: 200, description: "Variation options fetched successfully")]
    )]
    public function index(Request $request)
    {
        try {
            $query = VariationOption::with('variation');

            // Filter by variation
            if ($request->has('variation_id')) {
                $query->where('variation_id', $request->variation_id);
            }

            // Sort by display_order
            $query->orderBy('display_order', 'asc');

            $options = $query->get();

            return Response::success($options, 'Variation options fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Get single variation option
     */
    public function show($id)
    {
        try {
            $option = VariationOption::with(['variation', 'variationStocks'])->find($id);

            if (!$option) {
                return Response::error('', 'Variation option not found', 404);
            }

            return Response::success($option, 'Variation option fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Create new variation option
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'variation_id' => 'required|exists:variations,id',
                'name' => 'required|string|max:255',
                'value' => 'nullable|string|max:255',
                'additional_price' => 'nullable|numeric|min:0',
                'is_default' => 'nullable|boolean',
                'display_order' => 'nullable|integer|min:0',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            // If this is set as default, unset other defaults for this variation
            if ($request->is_default) {
                VariationOption::where('variation_id', $request->variation_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $option = VariationOption::create([
                'variation_id' => $request->variation_id,
                'name' => $request->name,
                'value' => $request->value ?? $request->name,
                'additional_price' => $request->additional_price ?? 0,
                'is_default' => $request->is_default ?? false,
                'display_order' => $request->display_order ?? 0,
                'metadata' => $request->metadata,
            ]);

            return Response::success($option->load('variation'), 'Variation option created successfully', 201);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Update variation option
     */
    public function update(Request $request, $id)
    {
        try {
            $option = VariationOption::find($id);

            if (!$option) {
                return Response::error('', 'Variation option not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'variation_id' => 'sometimes|required|exists:variations,id',
                'name' => 'sometimes|required|string|max:255',
                'value' => 'nullable|string|max:255',
                'additional_price' => 'nullable|numeric|min:0',
                'is_default' => 'nullable|boolean',
                'display_order' => 'nullable|integer|min:0',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $updateData = $request->only(['variation_id', 'name', 'value', 'additional_price', 'is_default', 'display_order', 'metadata']);

            // If this is set as default, unset other defaults for this variation
            if ($request->has('is_default') && $request->is_default) {
                $variationId = $request->variation_id ?? $option->variation_id;
                VariationOption::where('variation_id', $variationId)
                    ->where('id', '!=', $id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $option->update($updateData);

            return Response::success($option->fresh()->load('variation'), 'Variation option updated successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Delete variation option
     */
    public function destroy($id)
    {
        try {
            $option = VariationOption::find($id);

            if (!$option) {
                return Response::error('', 'Variation option not found', 404);
            }

            // Check if option is used in any variation stocks
            if ($option->variationStocks()->count() > 0) {
                return Response::error('', 'Cannot delete variation option that is used in variation stocks', 400);
            }

            $option->delete();

            return Response::success('', 'Variation option deleted successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
