<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::create('projects', function (Blueprint $table): void {
			$table->id();
			$table->string('name');
			$table->text('description')->nullable();
			$table->string('key')->unique()->comment('Unique project code like "PROJ"');
			$table->string('status')->default('active');
			$table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
			$table->timestamps();
			$table->softDeletes();

			// Indices for frequently queried columns
			$table->index('status');
			$table->index('owner_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::dropIfExists('projects');
	}
};
