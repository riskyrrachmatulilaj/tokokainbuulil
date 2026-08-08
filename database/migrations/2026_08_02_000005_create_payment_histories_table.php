<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 40)->index();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('debt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collective_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('payment_type', ['installment', 'collective'])->index();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'payment_date']);
            $table->index(['debt_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};
