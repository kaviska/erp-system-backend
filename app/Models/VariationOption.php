<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VariationOption extends Model
{
    /** @use HasFactory<\Database\Factories\VariationOptionFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'variation_id',
        'name',
        'value',
        'additional_price',
        'is_default',
        'display_order',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array',
        'additional_price' => 'decimal:2',
        'display_order' => 'integer',
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class);
    }

    public function variationStocks(): BelongsToMany
    {
        return $this->belongsToMany(VariationStock::class, 'variation_stock_options')
            ->withTimestamps();
    }
}
