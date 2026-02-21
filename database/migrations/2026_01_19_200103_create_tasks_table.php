<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('tasks', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('project_id')->constrained()->cascadeOnDelete();
			$table->foreignId('sprint_id')->nullable()->constrained()->nullOnDelete();
			$table->foreignId('parent_id')->nullable()->constrained('tasks')->cascadeOnDelete();
			$table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
			$table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
			$table->string('title');
			$table->text('description')->nullable();
			$table->string('type')->default('task');
			$table->string('status')->default('todo');
			$table->string('priority')->default('medium');
			$table->unsignedTinyInteger('story_points')->nullable();
			$table->timestamp('due_date')->nullable();
			$table->unsignedInteger('position')->default(0)->comment('For ordering tasks');
			$table->timestamps();
			$table->softDeletes();

			// Indices for frequently queried columns
			$table->index(['project_id', 'status']);
			$table->index(['sprint_id', 'status']);
			$table->index('assigned_to');
			$table->index('reporter_id');
			$table->index('parent_id');
			$table->index('type');
			$table->index('priority');
			$table->index('due_date');
			$table->index(['project_id', 'position']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('tasks');
	}
};
