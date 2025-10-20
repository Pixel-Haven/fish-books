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
        Schema::create('bill_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bill_id');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->uuid('fish_type_id');
            $table->foreign('fish_type_id')->references('id')->on('fish_types')->onDelete('restrict');
            $table->decimal('quantity', 10, 2);
            $table->decimal('price_per_kilo', 10, 2);
            $table->decimal('line_total', 14, 2);
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('bill_id');
            $table->index('fish_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_line_items');
    }
};
