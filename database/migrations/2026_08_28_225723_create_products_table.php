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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('type', 20)->default('simple')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('featured')->default(false)->index();
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 12, 4)->nullable();
            $table->decimal('compare_at_price', 12, 4)->nullable();
            $table->decimal('cost_price', 12, 4)->nullable();
            $table->boolean('manage_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(0);
            $table->string('stock_status', 20)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
