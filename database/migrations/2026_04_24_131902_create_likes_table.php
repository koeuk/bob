<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('likeable');
            $table->enum('type', ['like', 'love', 'haha', 'wow', 'sad', 'angry', 'bookmark'])->default('like');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'likeable_id', 'likeable_type', 'type'], 'likes_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
