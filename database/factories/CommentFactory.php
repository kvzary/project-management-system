<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory {
	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return [
			'commentable_type'	=> Task::class,
			'commentable_id'	=> Task::factory(),
			'user_id'			=> User::factory(),
			'body'				=> fake()->paragraphs(fake()->numberBetween(1, 3), true),
		];
	}

	/**
	 * Indicate that the comment is for a task.
	 */
	public function forTask(?Task $task = null): static {
		return $this->state(fn (array $attributes): array => [
			'commentable_type'	=> Task::class,
			'commentable_id'	=> $task?->id ?? Task::factory(),
		]);
	}

	/**
	 * Indicate that the comment is short.
	 */
	public function short(): static {
		return $this->state(fn (array $attributes): array => [
			'body' => fake()->sentence(fake()->numberBetween(5, 15)),
		]);
	}

	/**
	 * Indicate that the comment is long.
	 */
	public function long(): static {
		return $this->state(fn (array $attributes): array => [
			'body' => fake()->paragraphs(fake()->numberBetween(3, 5), true),
		]);
	}
}
