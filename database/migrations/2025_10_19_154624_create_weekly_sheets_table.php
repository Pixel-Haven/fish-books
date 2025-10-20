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
        Schema::create('weekly_sheets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('week_start');
            $table->date('week_end');
            $table->string('label')->nullable();
            $table->enum('status', ['DRAFT', 'READY_FOR_APPROVAL', 'FINALIZED', 'PAID'])->default('DRAFT');
            $table->decimal('total_sales', 14, 2)->nullable();
            $table->decimal('total_expenses', 14, 2)->nullable();
            $table->decimal('total_weekly_payout', 14, 2)->nullable();
            $table->decimal('owner_share', 14, 2)->nullable();
            $table->decimal('crew_share', 14, 2)->nullable();
            $table->datetime('processed_at')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('week_start');
            $table->index('week_end');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_sheets');
    }
};
