<?php

namespace Database\Factories;

use App\Enums\SprintStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sprint>
 */
class SprintFactory extends Factory {
	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		$startDate = fake()->dateTimeBetween('-1 month', '+1 month');
		$endDate = fake()->dateTimeBetween($startDate, '+2 weeks');

		return [
			'project_id'	=> Project::factory(),
			'name'			=> 'Sprint ' . fake()->numberBetween(1, 50),
			'goal'			=> fake()->sentence(10),
			'start_date'	=> $startDate,
			'end_date'		=> $endDate,
			'status'		=> fake()->randomElement(SprintStatus::cases())->value,
		];
	}

	/**
	 * Indicate that the sprint is in planning.
	 */
	public function planning(): static {
		return $this->state(fn (array $attributes): array => [
			'status'		=> SprintStatus::PLANNING->value,
			'start_date'	=> fake()->dateTimeBetween('+1 week', '+2 weeks'),
			'end_date'		=> fake()->dateTimeBetween('+3 weeks', '+4 weeks'),
		]);
	}

	/**
	 * Indicate that the sprint is active.
	 */
	public function active(): static {
		return $this->state(fn (array $attributes): array => [
			'status'		=> SprintStatus::ACTIVE->value,
			'start_date'	=> fake()->dateTimeBetween('-1 week', 'now'),
			'end_date'		=> fake()->dateTimeBetween('+1 week', '+2 weeks'),
		]);
	}

	/**
	 * Indicate that the sprint is completed.
	 */
	public function completed(): static {
		return $this->state(fn (array $attributes): array => [
			'status'		=> SprintStatus::COMPLETED->value,
			'start_date'	=> fake()->dateTimeBetween('-1 month', '-2 weeks'),
			'end_date'		=> fake()->dateTimeBetween('-2 weeks', '-1 week'),
		]);
	}
}
