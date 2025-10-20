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
        Schema::create('trips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vessel_id');
            $table->foreign('vessel_id')->references('id')->on('vessels')->onDelete('restrict');
            $table->date('date');
            $table->enum('status', ['DRAFT', 'ONGOING', 'CLOSED'])->default('DRAFT');
            $table->decimal('total_sales', 14, 2)->nullable();
            $table->decimal('balance', 14, 2)->nullable();
            $table->decimal('net_total', 14, 2)->nullable();
            $table->decimal('owner_share', 14, 2)->nullable();
            $table->decimal('crew_share', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->datetime('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('vessel_id');
            $table->index('date');
            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
