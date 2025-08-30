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
        Schema::create('orders', function (Blueprint $table) {
             $table->id();
             $table->string('location');
             $table->unsignedBigInteger('user_id')->nullable();
             $table->decimal('total_price', 10, 2)->nullable();
             $table->enum('status', ['pending', 'paid', 'refunded', 'partial', 'failed'])->default('pending');
             $table->enum('payment_method', ['cash', 'stripe'])->default('cash');
             $table->unsignedInteger('amount_platform_fee'); // total platform fee (all vendors)
             $table->string('stripe_payment_intent')->nullable();
             $table->string('stripe_session_id')->nullable();
             $table->json('meta')->nullable();
             $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
