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
        Schema::create('marquiz', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->json('body')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('city')->nullable();
            $table->string('roistat')->nullable();
            $table->integer('status')->default(0);
            $table->integer('lead_id')->nullable();
            $table->integer('contact_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marquiz');
    }
};
