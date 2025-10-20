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
        Schema::create('crew_credits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('weekly_sheet_id');
            $table->foreign('weekly_sheet_id')->references('id')->on('weekly_sheets')->onDelete('cascade');
            $table->uuid('crew_member_id');
            $table->foreign('crew_member_id')->references('id')->on('crew_members')->onDelete('restrict');
            $table->decimal('amount', 14, 2);
            $table->text('description')->nullable();
            $table->datetime('credit_date')->useCurrent();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('weekly_sheet_id');
            $table->index('crew_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_credits');
    }
};
