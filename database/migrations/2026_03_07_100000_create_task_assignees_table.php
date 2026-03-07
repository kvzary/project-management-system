<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['task_id', 'user_id']);
            $table->timestamps();
        });

        // Migrate existing assigned_to values into the pivot
        DB::table('tasks')
            ->whereNotNull('assigned_to')
            ->select('id', 'assigned_to')
            ->orderBy('id')
            ->each(function ($task) {
                DB::table('task_assignees')->insertOrIgnore([
                    'task_id'    => $task->id,
                    'user_id'    => $task->assigned_to,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
    }
};
