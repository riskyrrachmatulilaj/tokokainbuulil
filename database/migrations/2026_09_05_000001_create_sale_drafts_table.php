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
        Schema::create('sale_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_name')->nullable();
            $table->json('cart_data');
            $table->foreignId('receivable_party_id')->nullable()->constrained('receivable_parties')->nullOnDelete();
            $table->string('payment_method')->default('cash');
            $table->date('sale_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_drafts');
    }
};
