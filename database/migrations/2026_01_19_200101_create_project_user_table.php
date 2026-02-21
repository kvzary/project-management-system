<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('project_user', function (Blueprint $table): void {
			$table->id();
			$table->foreignId('project_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->string('role')->default('member')->comment('admin, member, viewer');
			$table->timestamps();

			// Ensure a user can only have one role per project
			$table->unique(['project_id', 'user_id']);

			// Indices for querying members
			$table->index('project_id');
			$table->index('user_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('project_user');
	}
};
