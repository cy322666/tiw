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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('lead_id')->nullable();
            $table->boolean('wait')->nullable();
            $table->bigInteger('msg_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->boolean('is_bot')->nullable();
            $table->string('first_name')->nullable();
            $table->string('username')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
