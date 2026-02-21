<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Remove repository_url if it exists
            if (Schema::hasColumn('projects', 'repository_url')) {
                $table->dropColumn('repository_url');
            }

            // Add branch and pull request fields
            $table->string('branch')->nullable()->after('product_manager_id');
            $table->string('pull_request_url')->nullable()->after('branch');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['branch', 'pull_request_url']);
            $table->string('repository_url')->nullable();
        });
    }
};
