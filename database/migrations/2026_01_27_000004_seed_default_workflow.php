<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
	public function up(): void {
		$workflowId = DB::table('workflows')->insertGetId([
			'name' => 'Default Workflow',
			'description' => 'Standard workflow with To Do, In Progress, In Review, and Done statuses.',
			'is_default' => true,
			'created_at' => now(),
			'updated_at' => now(),
		]);

		$statuses = [
			['slug' => 'todo', 'name' => 'To Do', 'color' => 'gray', 'position' => 0, 'is_completed' => false],
			['slug' => 'in_progress', 'name' => 'In Progress', 'color' => 'info', 'position' => 1, 'is_completed' => false],
			['slug' => 'in_review', 'name' => 'In Review', 'color' => 'warning', 'position' => 2, 'is_completed' => false],
			['slug' => 'done', 'name' => 'Done', 'color' => 'success', 'position' => 3, 'is_completed' => true],
		];

		foreach ($statuses as $status) {
			DB::table('workflow_statuses')->insert(array_merge($status, [
				'workflow_id' => $workflowId,
				'created_at' => now(),
				'updated_at' => now(),
			]));
		}

		// Assign default workflow to all existing projects
		DB::table('projects')->whereNull('workflow_id')->update(['workflow_id' => $workflowId]);
	}

	public function down(): void {
		DB::table('projects')->update(['workflow_id' => null]);
		DB::table('workflow_statuses')->delete();
		DB::table('workflows')->delete();
	}
};
