<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('error_tracker_events')) {
            return;
        }

        Schema::create('error_tracker_events', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('source', 20)->default('exception'); // exception | log
            $table->string('level', 20)->default('error');
            $table->string('exception_class')->nullable();
            $table->text('message');
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->longText('trace')->nullable();
            $table->longText('context')->nullable(); // JSON: request, user, tenant, log context
            $table->string('environment', 50)->nullable();
            $table->unsignedInteger('occurrences')->default(1);
            $table->unsignedInteger('occurrences_since_notified')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_tracker_events');
    }
};
