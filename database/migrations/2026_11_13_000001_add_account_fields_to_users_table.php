<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_name')->nullable()->after('name');
            // Canonical Nigerian mobile format, e.g. +2348031234567.
            $table->string('phone', 20)->nullable()->after('email');
            // A requested but not yet verified new email address.
            $table->string('pending_email')->nullable()->after('email_verified_at');
            $table->json('email_preferences')->nullable()->after('pending_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['business_name', 'phone', 'pending_email', 'email_preferences']);
        });
    }
};
