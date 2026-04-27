<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')
                ->references('id')->on('telegraph_chats')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->index('created_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
