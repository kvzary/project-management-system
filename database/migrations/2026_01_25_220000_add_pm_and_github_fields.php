<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		// Add fields to projects table
		Schema::table('projects', function (Blueprint $table): void {
			$table->string('repository_url')->nullable()->after('status');
			$table->foreignId('product_manager_id')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
		});

		// Add fields to tasks table
		Schema::table('tasks', function (Blueprint $table): void {
			$table->string('branch')->nullable()->after('position');
			$table->foreignId('product_manager_id')->nullable()->after('reporter_id')->constrained('users')->nullOnDelete();
		});

		// Create task_creators pivot table for multiple creators
		Schema::create('task_creators', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('task_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->timestamps();

			$table->unique(['task_id', 'user_id']);
		});

		// Create project_creators pivot table for multiple creators
		Schema::create('project_creators', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('project_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->timestamps();

			$table->unique(['project_id', 'user_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('project_creators');
		Schema::dropIfExists('task_creators');

		Schema::table('tasks', function (Blueprint $table): void {
			$table->dropForeign(['product_manager_id']);
			$table->dropColumn(['branch', 'product_manager_id']);
		});

		Schema::table('projects', function (Blueprint $table): void {
			$table->dropForeign(['product_manager_id']);
			$table->dropColumn(['repository_url', 'product_manager_id']);
		});
	}
};
