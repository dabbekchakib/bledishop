<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('discountable_type')->nullable(); // App\Models\Coupon|DiscountRule|Promotion
            $table->unsignedBigInteger('discountable_id')->nullable();
            $table->string('kind', 20); // coupon | rule | promotion
            $table->string('code')->nullable(); // coupon code
            $table->string('name')->nullable();
            $table->string('type', 20)->default('percentage');
            $table->decimal('value', 12, 4)->default(0);
            $table->unsignedBigInteger('amount')->default(0); // applied amount in cents
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_discounts');
    }
};
