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
        Schema::create('bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('trip_id');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->enum('bill_type', ['TODAY_SALES', 'PREVIOUS_DAY_SALES', 'OTHER'])->default('TODAY_SALES');
            $table->string('bill_no');
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->date('bill_date');
            $table->enum('payment_status', ['PAID', 'UNPAID', 'PARTIAL'])->default('UNPAID');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('trip_id');
            $table->index('bill_type');
            $table->index('bill_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
