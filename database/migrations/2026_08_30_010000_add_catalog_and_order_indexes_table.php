<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds targeted indexes for the most common catalog and order queries:
     * dashboard period filtering and totals, category public filtering, and
     * variant price sorting. Existing FK columns (orders.user_id,
     * categories.parent_id, product_variants.product_id) are already indexed
     * by MySQL InnoDB, so they are not duplicated here.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'orders_created_at_index');
            $table->index('total', 'orders_total_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('status', 'categories_status_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('price', 'product_variants_price_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_created_at_index');
            $table->dropIndex('orders_total_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_status_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('product_variants_price_index');
        });
    }
};
