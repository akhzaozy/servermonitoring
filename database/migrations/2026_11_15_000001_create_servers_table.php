<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('remote'); // local | remote
            $table->string('base_url')->nullable();
            $table->text('token')->nullable();
            $table->string('portainer_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('system_metric_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('system_metric_logs', function (Blueprint $table) {
            $table->dropColumn('server_id');
        });

        Schema::dropIfExists('servers');
    }
};
