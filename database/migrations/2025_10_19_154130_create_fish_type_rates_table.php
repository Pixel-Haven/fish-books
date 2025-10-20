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
        Schema::create('fish_type_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fish_type_id');
            $table->foreign('fish_type_id')->references('id')->on('fish_types')->onDelete('cascade');
            $table->decimal('rate_per_kilo', 10, 2);
            $table->date('rate_effective_from')->nullable();
            $table->date('rate_effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['fish_type_id', 'rate_effective_from', 'rate_effective_to'], 'fish_type_rates_dates_idx');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fish_type_rates');
    }
};
