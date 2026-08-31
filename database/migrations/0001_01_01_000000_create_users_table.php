<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

            $table->id();
            $table->string('wallet_address', 42)->unique()->index();
            $table->string('password');
            $table->string('email')->nullable()->unique();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->string('referral_code', 20)->unique();
            $table->unsignedBigInteger('referred_id')->nullable();
            $table->decimal('mind_balance', 24, 8)->default(0.00000000);
            $table->enum('role', ['user','admin',])->default('user')->index();
            $table->timestamps();
            $table->foreign('referred_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('users')->insert([
            'wallet_address' => '0x0000000000000000000000000000000000000001',
            'password'       => Hash::make('Admin@1234'),
            'email'          => 'admin@mindcoin.com',
            'name'           => 'Super Admin',
            'address'        => null,
            'referral_code'  => 'ADMIN001',
            'referred_id'    => null,
            'mind_balance'   => 0,
            'role'           => 'admin',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
