<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'seller_id',
        'category_id',
        'sub_category_id',
        'name',
        'slug',
        'sku',
        'image_path',
        'barcode',
        'type',
        'brand',
        'short_description',
        'description',
        'lead_time',
        'price',
        'sale_price',
        'currency',
        'stock_quantity',
        'track_inventory',
        'is_published',
        'published_at',
        'status',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'track_inventory' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'lead_time' => 'integer',
        'stock_quantity' => 'integer',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class);
    }

    public function variationStocks(): HasMany
    {
        return $this->hasMany(VariationStock::class);
    }
}
