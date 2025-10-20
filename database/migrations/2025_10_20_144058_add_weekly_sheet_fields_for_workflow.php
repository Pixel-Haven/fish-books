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
        // Add vessel_id to weekly_sheets
        Schema::table('weekly_sheets', function (Blueprint $table) {
            $table->uuid('vessel_id')->after('id');
            $table->foreign('vessel_id')->references('id')->on('vessels')->onDelete('restrict');
            $table->text('description')->nullable()->after('label');
            $table->index('vessel_id');
        });
        
        // Add weekly_sheet_id, day_of_week, is_fishing_day to trips
        Schema::table('trips', function (Blueprint $table) {
            $table->uuid('weekly_sheet_id')->nullable()->after('vessel_id');
            $table->foreign('weekly_sheet_id')->references('id')->on('weekly_sheets')->onDelete('restrict');
            $table->string('day_of_week', 10)->nullable()->after('date'); // SAT, SUN, MON, TUE, WED, THU
            $table->boolean('is_fishing_day')->default(true)->after('day_of_week');
            $table->index('weekly_sheet_id');
            $table->index('day_of_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['weekly_sheet_id']);
            $table->dropIndex(['weekly_sheet_id']);
            $table->dropIndex(['day_of_week']);
            $table->dropColumn(['weekly_sheet_id', 'day_of_week', 'is_fishing_day']);
        });
        
        Schema::table('weekly_sheets', function (Blueprint $table) {
            $table->dropForeign(['vessel_id']);
            $table->dropIndex(['vessel_id']);
            $table->dropColumn(['vessel_id', 'description']);
        });
    }
};
