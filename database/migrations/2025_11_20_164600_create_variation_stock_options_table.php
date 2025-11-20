<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('variation_stock_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_option_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['variation_stock_id', 'variation_option_id'], 'vso_stock_option_unique');
            $table->index('variation_stock_id', 'vso_stock_id_idx');
            $table->index('variation_option_id', 'vso_option_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variation_stock_options');
    }
};
