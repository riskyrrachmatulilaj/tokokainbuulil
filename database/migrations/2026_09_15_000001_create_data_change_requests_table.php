<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('requestable_type');
            $table->unsignedBigInteger('requestable_id');
            $table->index(['requestable_type', 'requestable_id']);
            $table->string('request_type'); // 'edit', 'void'
            $table->text('reason');
            $table->json('original_data');
            $table->json('proposed_data')->nullable();
            $table->string('status')->default('pending')->index(); // 'pending', 'approved', 'rejected'
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_change_requests');
    }
};
