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
        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('trip_id');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->uuid('crew_member_id');
            $table->foreign('crew_member_id')->references('id')->on('crew_members')->onDelete('restrict');
            $table->enum('role', ['BAITING', 'FISHING', 'CHUMMER', 'DIVING', 'HELPER', 'SPECIAL']);
            $table->decimal('helper_ratio', 5, 2)->nullable()->default(1.0);
            $table->decimal('kilos_assigned', 10, 2)->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->index('trip_id');
            $table->index('crew_member_id');
            $table->unique(['trip_id', 'crew_member_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_assignments');
    }
};
