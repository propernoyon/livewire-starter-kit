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
        Schema::create('shop_information', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name');
            $table->string('address');
            $table->string('email');
            $table->string('mobile');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            //$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_information');
    }
};
