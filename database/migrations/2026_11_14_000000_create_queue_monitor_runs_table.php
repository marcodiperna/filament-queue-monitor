<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('queue-monitor.history.table', 'queue_monitor_runs'), function (Blueprint $table) {
            $table->id();
            $table->string('job_uuid')->nullable()->index();
            $table->string('name');
            $table->string('queue')->nullable()->index();
            $table->string('connection')->nullable();
            $table->string('status', 20)->index();
            $table->unsignedInteger('attempt')->default(1);
            $table->unsignedBigInteger('operator_id')->nullable()->index();
            $table->string('batch_id', 36)->nullable()->index();
            $table->timestamp('started_at', 3)->index();
            $table->timestamp('finished_at', 3)->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->text('exception')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('queue-monitor.history.table', 'queue_monitor_runs'));
    }
};
