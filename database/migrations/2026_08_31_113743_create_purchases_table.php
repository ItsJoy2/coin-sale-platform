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
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('invoice_id')->nullable()->unique();
            $table->string('payment_address')->nullable();
            $table->string('coupon_code')->nullable();
            $table->decimal('usdt_amount', 24, 8);
            $table->decimal('payable_usdt', 24, 8)->nullable();
            $table->decimal('received_usdt', 24, 8)->nullable();
            $table->decimal('mind_price', 24, 8);
            $table->decimal('mind_amount', 24, 8);
            $table->decimal('bonus_percentage', 10, 4)->default(0);
            $table->decimal('bonus_mind', 24, 8)->default(0);
            $table->decimal('total_mind', 24, 8);
            $table->unsignedInteger('slot')->nullable();
            $table->string('tx_hash')->nullable()->index();
            $table->enum('status', ['pending','processing','completed','failed','expired',])->default('pending')->index();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->text('failure_reason')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
