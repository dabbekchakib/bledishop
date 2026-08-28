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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 12, 4)->nullable();
            $table->decimal('compare_at_price', 12, 4)->nullable();
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->boolean('manage_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(0);
            $table->string('stock_status', 20)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('image')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
