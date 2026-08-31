<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 191)->nullable();
            $table->timestamps();

            // Per-user and per-session uniqueness. In MySQL, NULLs are allowed
            // to repeat inside unique indexes, so guest rows (user_id = NULL)
            // are de-duplicated by session_id and user rows (session_id = NULL)
            // by user_id.
            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);

            $table->index(['user_id']);
            $table->index(['session_id']);

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
