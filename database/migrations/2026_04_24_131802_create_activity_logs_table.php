<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            // Nullable + nullOnDelete: cascading would erase an admin's entire
            // audit trail at the moment they are removed — destroying the
            // evidence of what they did. Nullable also lets non-HTTP callers
            // (console commands, queued jobs) write logs with no auth() user.
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id']);
            $table->index('admin_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
