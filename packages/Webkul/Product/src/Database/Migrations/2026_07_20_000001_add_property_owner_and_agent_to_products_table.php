<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('owner_id')->nullable()->after('price');
            $table->foreign('owner_id')->references('id')->on('persons')->nullOnDelete();

            $table->unsignedInteger('assigned_agent_id')->nullable()->after('owner_id');
            $table->foreign('assigned_agent_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['assigned_agent_id']);
            $table->dropColumn(['owner_id', 'assigned_agent_id']);
        });
    }
};
