<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('call_outcome')->nullable()->after('comment');
            $table->unsignedInteger('call_duration')->nullable()->after('call_outcome');
            $table->dateTime('follow_up_at')->nullable()->after('schedule_to');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['call_outcome', 'call_duration', 'follow_up_at']);
        });
    }
};
