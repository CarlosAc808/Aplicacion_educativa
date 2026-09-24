<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('lives')->default(7)->after('is_admin');
            $table->timestamp('lives_reset_at')->nullable()->after('lives');
            $table->timestamp('premium_until')->nullable()->after('lives_reset_at');
            $table->string('last_stripe_session_id')->nullable()->unique()->after('premium_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['lives', 'lives_reset_at', 'premium_until', 'last_stripe_session_id']);
        });
    }
};