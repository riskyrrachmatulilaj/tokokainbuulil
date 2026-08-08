<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivable_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 40)->index();
            $table->foreignId('receivable_party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('receivable_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('receivable_installments')->nullOnDelete();
            $table->foreignId('collective_payment_id')->nullable()->constrained('receivable_collective_payments')->nullOnDelete();
            $table->enum('payment_type', ['installment', 'collective'])->index();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['receivable_party_id', 'payment_date'], 'rph_party_date_idx');
            $table->index(['receivable_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_payment_histories');
    }
};
