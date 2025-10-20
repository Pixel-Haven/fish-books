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
        Schema::create('weekly_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('weekly_sheet_id');
            $table->foreign('weekly_sheet_id')->references('id')->on('weekly_sheets')->onDelete('cascade');
            $table->uuid('crew_member_id');
            $table->foreign('crew_member_id')->references('id')->on('crew_members')->onDelete('restrict');
            $table->decimal('base_amount', 14, 2)->default(0);
            $table->decimal('credit_deduction', 14, 2)->default(0);
            $table->decimal('final_amount', 14, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->datetime('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('weekly_sheet_id');
            $table->index('crew_member_id');
            $table->index('is_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_payouts');
    }
};
