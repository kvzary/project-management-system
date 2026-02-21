<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory {
	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		$name = fake()->catchPhrase();
		$words = explode(' ', $name);
		$key = strtoupper(substr($words[0], 0, 3) . substr($words[1] ?? 'PRJ', 0, 1));

		return [
			'name'			=> $name,
			'description'	=> fake()->paragraph(3),
			'key'			=> $key . fake()->unique()->numberBetween(1, 999),
			'status'		=> fake()->randomElement(ProjectStatus::cases())->value,
			'owner_id'		=> User::factory(),
		];
	}

	/**
	 * Indicate that the project is active.
	 */
	public function active(): static {
		return $this->state(fn (array $attributes): array => [
			'status' => ProjectStatus::ACTIVE->value,
		]);
	}

	/**
	 * Indicate that the project is archived.
	 */
	public function archived(): static {
		return $this->state(fn (array $attributes): array => [
			'status' => ProjectStatus::ARCHIVED->value,
		]);
	}

	/**
	 * Indicate that the project is on hold.
	 */
	public function onHold(): static {
		return $this->state(fn (array $attributes): array => [
			'status' => ProjectStatus::ON_HOLD->value,
		]);
	}
}
