<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 40)->unique();
            $table->date('sale_date');
            $table->enum('payment_method', ['cash', 'receivable'])->default('cash');
            $table->foreignId('receivable_party_id')->nullable()->constrained('receivable_parties')->nullOnDelete();
            $table->foreignId('receivable_id')->nullable()->constrained('receivables')->nullOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('received_amount', 15, 2)->nullable();
            $table->decimal('change_amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sale_date', 'payment_method']);
            $table->index('sale_date');
            $table->index('receivable_party_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
