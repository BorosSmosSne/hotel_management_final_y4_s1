<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Deleting a user keeps their history (user_id becomes null).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);                       // created | updated | deleted | login | logout | login_failed
            $table->string('model', 60)->nullable();            // Booking, Room, User, Payment ... (null for logins)
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('description');
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();       // append-only: there is no updated_at

            $table->index('action');
            $table->index('created_at');
            $table->index(['model', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
