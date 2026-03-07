<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Nullable at DB level so existing projects don't break;
            // required at application/form level for new projects.
            $table->foreignId('department_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Department::class);
            $table->dropColumn('department_id');
        });
    }
};
