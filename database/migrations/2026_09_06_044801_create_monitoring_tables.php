<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitored_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('stack_type')->default('laravel'); // nextjs, laravel, native_php, static, api, other
            $table->integer('port')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('last_status_code')->nullable();
            $table->float('last_response_time_ms')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('ssl_valid')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->string('expected_keyword')->nullable();
            $table->string('vhost_path')->nullable(); // linked nginx vhost file if any
            $table->timestamps();
        });

        Schema::create('site_check_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitored_site_id')->constrained('monitored_sites')->cascadeOnDelete();
            $table->integer('status_code')->nullable();
            $table->float('response_time_ms')->default(0);
            $table->boolean('is_online')->default(false);
            $table->string('error_message')->nullable();
            $table->timestamp('checked_at')->useCurrent();
            $table->index(['monitored_site_id', 'checked_at']);
        });

        Schema::create('system_metric_logs', function (Blueprint $table) {
            $table->id();
            $table->float('cpu_usage_percent')->default(0);
            $table->float('ram_usage_percent')->default(0);
            $table->float('ram_used_mb')->default(0);
            $table->float('ram_total_mb')->default(0);
            $table->float('disk_usage_percent')->default(0);
            $table->float('disk_read_kb_s')->default(0);
            $table->float('disk_write_kb_s')->default(0);
            $table->float('temperature_celsius')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_metric_logs');
        Schema::dropIfExists('site_check_logs');
        Schema::dropIfExists('monitored_sites');
    }
};
