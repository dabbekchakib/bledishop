<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('type', 20)->default('percentage'); // percentage | fixed | promo_price
            $table->decimal('value', 12, 4)->default(0);
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('cumulative')->default(false);
            $table->boolean('active')->default(true);
            $table->decimal('min_subtotal', 12, 4)->nullable();
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedInteger('min_items')->nullable();
            $table->json('product_ids')->nullable();
            $table->json('category_ids')->nullable();
            $table->json('brand_ids')->nullable();
            $table->json('customer_ids')->nullable();
            $table->boolean('first_purchase_only')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['active', 'priority']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_rules');
    }
};
