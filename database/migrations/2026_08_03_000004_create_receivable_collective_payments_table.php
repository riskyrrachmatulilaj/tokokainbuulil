<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivable_collective_payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 40)->unique();
            $table->foreignId('receivable_party_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['receivable_party_id', 'payment_date'], 'rcp_party_date_idx');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_collective_payments');
    }
};
