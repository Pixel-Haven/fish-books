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
        Schema::create('crew_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('local_name')->nullable();
            $table->boolean('active')->default(true);
            $table->string('phone')->nullable();
            $table->string('id_card_no')->nullable()->unique();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('active');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_members');
    }
};
