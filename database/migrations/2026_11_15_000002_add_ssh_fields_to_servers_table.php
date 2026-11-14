<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('ssh_host')->nullable()->after('base_url');
            $table->unsignedInteger('ssh_port')->default(22)->after('ssh_host');
            $table->string('ssh_user')->nullable()->after('ssh_port');
            $table->text('ssh_password')->nullable()->after('ssh_user');
            $table->text('ssh_key')->nullable()->after('ssh_password');
            $table->boolean('use_cloudflare')->default(false)->after('ssh_key');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn([
                'ssh_host',
                'ssh_port',
                'ssh_user',
                'ssh_password',
                'ssh_key',
                'use_cloudflare',
            ]);
        });
    }
};
