<?php

namespace App\Http\Controllers;

use App\Helper\Response;
use Illuminate\Http\Request;
use App\Models\VariationStock;
use App\Models\Product;
use App\Models\VariationOption;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class VariationStockController extends Controller
{
    /**
     * Get all variation stocks
     */
    public function index(Request $request)
    {
        try {
            $query = VariationStock::with(['product', 'variationOptions']);

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by availability (quantity > 0)
            if ($request->has('available_only') && $request->available_only === 'true') {
                $query->where('quantity', '>', 0)->where('status', 'available');
            }

            // Search by SKU
            if ($request->has('search')) {
                $query->where('sku', 'like', '%' . $request->search . '%');
            }

            $stocks = $query->get();

            return Response::success($stocks, 'Variation stocks fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Get single variation stock
     */
    public function show($id)
    {
        try {
            $stock = VariationStock::with(['product', 'variationOptions.variation'])->find($id);

            if (!$stock) {
                return Response::error('', 'Variation stock not found', 404);
            }

            return Response::success($stock, 'Variation stock fetched successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Create new variation stock
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'sku' => 'required|string|unique:variation_stocks,sku',
                'image_path' => 'nullable|string|max:500',
                'price' => 'required|numeric|min:0',
                'quantity' => 'nullable|integer|min:0',
                'reserved_quantity' => 'nullable|integer|min:0',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'status' => 'nullable|string|in:available,reserved,sold_out',
                'option_values' => 'nullable|array',
                'metadata' => 'nullable|array',
                'variation_option_ids' => 'required|array|min:1',
                'variation_option_ids.*' => 'required|exists:variation_options,id',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            // Verify all variation options belong to the product's variations
            $product = Product::with('variations.options')->find($request->product_id);
            $productOptionIds = $product->variations->flatMap->options->pluck('id')->toArray();
            $requestOptionIds = $request->variation_option_ids;

            $invalidOptions = array_diff($requestOptionIds, $productOptionIds);
            if (!empty($invalidOptions)) {
                return Response::error('', 'Some variation options do not belong to this product', 400);
            }

            $stock = VariationStock::create([
                'product_id' => $request->product_id,
                'sku' => $request->sku,
                'image_path' => $request->image_path,
                'price' => $request->price,
                'quantity' => $request->quantity ?? 0,
                'reserved_quantity' => $request->reserved_quantity ?? 0,
                'low_stock_threshold' => $request->low_stock_threshold ?? 5,
                'status' => $request->status ?? 'available',
                'option_values' => $request->option_values,
                'metadata' => $request->metadata,
            ]);

            // Attach multiple variation options (e.g., Red color + XL size)
            $stock->variationOptions()->attach($request->variation_option_ids);

            return Response::success($stock->load(['product', 'variationOptions']), 'Variation stock created successfully', 201);
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Update variation stock
     */
    public function update(Request $request, $id)
    {
        try {
            $stock = VariationStock::find($id);

            if (!$stock) {
                return Response::error('', 'Variation stock not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'product_id' => 'sometimes|required|exists:products,id',
                'sku' => 'sometimes|required|string|unique:variation_stocks,sku,' . $id,
                'image_path' => 'nullable|string|max:500',
                'price' => 'sometimes|required|numeric|min:0',
                'quantity' => 'nullable|integer|min:0',
                'reserved_quantity' => 'nullable|integer|min:0',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'status' => 'nullable|string|in:available,reserved,sold_out',
                'option_values' => 'nullable|array',
                'metadata' => 'nullable|array',
                'variation_option_ids' => 'sometimes|required|array|min:1',
                'variation_option_ids.*' => 'required|exists:variation_options,id',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $updateData = $request->only([
                'product_id', 'sku', 'image_path', 'price', 'quantity',
                'reserved_quantity', 'low_stock_threshold', 'status',
                'option_values', 'metadata'
            ]);

            // Verify variation options if provided
            if ($request->has('variation_option_ids')) {
                $productId = $request->product_id ?? $stock->product_id;
                $product = Product::with('variations.options')->find($productId);
                $productOptionIds = $product->variations->flatMap->options->pluck('id')->toArray();
                $requestOptionIds = $request->variation_option_ids;

                $invalidOptions = array_diff($requestOptionIds, $productOptionIds);
                if (!empty($invalidOptions)) {
                    return Response::error('', 'Some variation options do not belong to this product', 400);
                }

                // Sync variation options
                $stock->variationOptions()->sync($request->variation_option_ids);
            }

            $stock->update($updateData);

            return Response::success($stock->fresh()->load(['product', 'variationOptions']), 'Variation stock updated successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Delete variation stock
     */
    public function destroy($id)
    {
        try {
            $stock = VariationStock::find($id);

            if (!$stock) {
                return Response::error('', 'Variation stock not found', 404);
            }

            $stock->delete();

            return Response::success('', 'Variation stock deleted successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }

    /**
     * Update stock quantity
     */
    public function updateQuantity(Request $request, $id)
    {
        try {
            $stock = VariationStock::find($id);

            if (!$stock) {
                return Response::error('', 'Variation stock not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:0',
                'operation' => 'nullable|string|in:set,increment,decrement',
            ]);

            if ($validator->fails()) {
                return Response::error($validator->errors(), 'Validation failed', 422);
            }

            $operation = $request->operation ?? 'set';

            switch ($operation) {
                case 'increment':
                    $stock->increment('quantity', $request->quantity);
                    break;
                case 'decrement':
                    $stock->decrement('quantity', $request->quantity);
                    break;
                default:
                    $stock->update(['quantity' => $request->quantity]);
            }

            // Update status based on quantity
            if ($stock->quantity <= 0) {
                $stock->update(['status' => 'sold_out']);
            } elseif ($stock->quantity <= $stock->low_stock_threshold) {
                $stock->update(['status' => 'reserved']);
            } else {
                $stock->update(['status' => 'available']);
            }

            return Response::success($stock->fresh(), 'Stock quantity updated successfully');
        } catch (\Throwable $th) {
            return Response::error($th->getMessage(), 'Something went wrong', 500);
        }
    }
}
