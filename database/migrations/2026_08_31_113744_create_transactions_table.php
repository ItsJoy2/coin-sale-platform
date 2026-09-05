<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('order_id', 14)->unique();
            $table->enum('type', ['purchase','tier_bonus','coupon_bonus','referral_bonus','withdrawal','admin_adjustment',])->default('purchase');
            $table->decimal('amount_mind', 24, 8)->default(0.00000000);
            $table->decimal('amount_usdt', 24, 8)->default(0.00000000);
            $table->unsignedBigInteger('source_user_id')->nullable();
            $table->decimal('rate_applied', 5, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->enum('status', ['pending','processing','completed','failed','cancelled',])->default('completed');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();
            $table->foreign('source_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index('purchase_id');
            $table->index('source_user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
