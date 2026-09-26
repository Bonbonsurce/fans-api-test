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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('username')->unique();
            $table->unsignedBigInteger('likes')->nullable();
            $table->unsignedInteger('revision')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('last_successful_refresh_at')->nullable();
            $table->json('last_failure')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
