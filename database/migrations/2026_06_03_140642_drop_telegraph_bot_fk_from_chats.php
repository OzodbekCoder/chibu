<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegraph_chats', function (Blueprint $table) {
            $table->dropForeign(['telegraph_bot_id']);
            $table->unsignedBigInteger('telegraph_bot_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('telegraph_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('telegraph_bot_id')->nullable(false)->change();
            $table->foreign('telegraph_bot_id')->references('id')->on('telegraph_bots');
        });
    }
};
