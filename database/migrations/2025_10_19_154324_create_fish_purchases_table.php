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
        Schema::create('fish_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('trip_id');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->uuid('fish_type_id');
            $table->foreign('fish_type_id')->references('id')->on('fish_types')->onDelete('restrict');
            $table->decimal('kilos', 10, 2);
            $table->decimal('rate_per_kilo', 10, 2);
            $table->decimal('amount', 14, 2);
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('trip_id');
            $table->index('fish_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_purchases');
    }
};
