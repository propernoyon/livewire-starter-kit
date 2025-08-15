<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code')->nullable();          // hashed OTP
            $table->timestamp('otp_expires_at')->nullable(); // expiry
            $table->timestamp('otp_last_sent_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['Active', 'Inactive'])->default('Inactive');
            $table->date('expire_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'otp_code','otp_expires_at','otp_last_sent_at','otp_attempts','is_verified','status', 'expire_date'
            ]);
        });
    }
};
