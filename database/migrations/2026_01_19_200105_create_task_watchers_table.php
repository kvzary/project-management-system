<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('task_watchers', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('task_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->timestamps();

			// Ensure a user can only watch a task once
			$table->unique(['task_id', 'user_id']);

			// Indices for querying watchers
			$table->index('task_id');
			$table->index('user_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('task_watchers');
	}
};
