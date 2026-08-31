<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('user_id');
            $table->decimal('usd_amount', 16, 2);
            $table->decimal('discount_amount_usd', 16, 2)->default(0.00);
            $table->decimal('final_usd_payable', 16, 2);
            $table->decimal('mind_price_usd', 16, 8);
            $table->decimal('base_mind_purchased', 24, 8);
            $table->decimal('tier_bonus_percentage', 5, 2)->default(0.00);
            $table->decimal('tier_bonus_mind', 24, 8)->default(0.00000000);
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->decimal('coupon_bonus_mind', 24, 8)->default(0.00000000);
            $table->decimal('total_mind_credited', 24, 8);
            $table->enum('status', ['pending','completed','failed','cancelled',])->default('pending');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('coupon_id')->references('id')->on('coupons')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
