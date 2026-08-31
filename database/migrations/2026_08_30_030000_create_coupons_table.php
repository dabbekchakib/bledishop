<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('type', 20)->default('percentage'); // percentage | fixed | free_shipping
            $table->decimal('value', 12, 4)->default(0);
            $table->decimal('min_subtotal', 12, 4)->nullable();
            $table->decimal('max_subtotal', 12, 4)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->json('product_ids')->nullable();
            $table->json('category_ids')->nullable();
            $table->json('brand_ids')->nullable();
            $table->json('excluded_product_ids')->nullable();
            $table->json('excluded_category_ids')->nullable();
            $table->boolean('cumulative')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('usage_count')->default(0);
            $table->timestamps();

            $table->index(['active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
