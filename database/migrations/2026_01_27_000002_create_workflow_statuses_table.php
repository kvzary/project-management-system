<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		Schema::create('workflow_statuses', function (Blueprint $table) {
			$table->id();
			$table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
			$table->string('slug');
			$table->string('name');
			$table->string('color')->default('gray');
			$table->integer('position')->default(0);
			$table->boolean('is_completed')->default(false);
			$table->timestamps();

			$table->unique(['workflow_id', 'slug']);
		});
	}

	public function down(): void {
		Schema::dropIfExists('workflow_statuses');
	}
};
