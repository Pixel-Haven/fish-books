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
        Schema::create('weekly_expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('weekly_sheet_id');
            $table->foreign('weekly_sheet_id')->references('id')->on('weekly_sheets')->onDelete('cascade');
            $table->string('category');
            $table->decimal('amount', 14, 2);
            $table->text('description')->nullable();
            $table->decimal('distributed_amount', 14, 2)->default(0);
            $table->enum('status', ['PENDING', 'APPROVED'])->default('PENDING');
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('weekly_sheet_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_expenses');
    }
};
