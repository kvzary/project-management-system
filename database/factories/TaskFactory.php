<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory {
	/**
	 * Define the model's default state.
	 *
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		$type = fake()->randomElement(TaskType::cases());
		$hasAssignee = fake()->boolean(70);
		$hasDueDate = fake()->boolean(60);
		$hasSprint = fake()->boolean(50);
		$statuses = ['todo', 'in_progress', 'in_review', 'done'];

		return [
			'project_id'	=> Project::factory(),
			'sprint_id'		=> $hasSprint ? Sprint::factory() : null,
			'parent_id'		=> null,
			'assigned_to'	=> $hasAssignee ? User::factory() : null,
			'reporter_id'	=> User::factory(),
			'title'			=> fake()->sentence(fake()->numberBetween(3, 8)),
			'description'	=> fake()->boolean(80) ? fake()->paragraphs(fake()->numberBetween(1, 3), true) : null,
			'type'			=> $type->value,
			'status'		=> fake()->randomElement($statuses),
			'priority'		=> fake()->randomElement(TaskPriority::cases())->value,
			'story_points'	=> $type === TaskType::STORY ? fake()->randomElement([1, 2, 3, 5, 8, 13]) : null,
			'due_date'		=> $hasDueDate ? fake()->dateTimeBetween('-1 month', '+2 months') : null,
			'position'		=> fake()->numberBetween(0, 1000),
		];
	}

	public function bug(): static {
		return $this->state(fn (array $attributes): array => [
			'type'		=> TaskType::BUG->value,
			'priority'	=> fake()->randomElement([TaskPriority::HIGH, TaskPriority::HIGHEST])->value,
		]);
	}

	public function story(): static {
		return $this->state(fn (array $attributes): array => [
			'type'			=> TaskType::STORY->value,
			'story_points'	=> fake()->randomElement([1, 2, 3, 5, 8, 13]),
		]);
	}

	public function epic(): static {
		return $this->state(fn (array $attributes): array => [
			'type'			=> TaskType::EPIC->value,
			'story_points'	=> fake()->randomElement([13, 21, 34]),
		]);
	}

	public function subtask(): static {
		return $this->state(fn (array $attributes): array => [
			'parent_id'		=> Task::factory(),
			'type'			=> TaskType::TASK->value,
			'story_points'	=> null,
		]);
	}

	public function backlog(): static {
		return $this->state(fn (array $attributes): array => [
			'sprint_id'	=> null,
			'status'	=> 'todo',
		]);
	}

	public function assigned(): static {
		return $this->state(fn (array $attributes): array => [
			'assigned_to' => User::factory(),
		]);
	}

	public function unassigned(): static {
		return $this->state(fn (array $attributes): array => [
			'assigned_to' => null,
		]);
	}

	public function todo(): static {
		return $this->state(fn (array $attributes): array => [
			'status' => 'todo',
		]);
	}

	public function inProgress(): static {
		return $this->state(fn (array $attributes): array => [
			'status'		=> 'in_progress',
			'assigned_to'	=> User::factory(),
		]);
	}

	public function inReview(): static {
		return $this->state(fn (array $attributes): array => [
			'status'		=> 'in_review',
			'assigned_to'	=> User::factory(),
		]);
	}

	public function done(): static {
		return $this->state(fn (array $attributes): array => [
			'status' => 'done',
		]);
	}

	public function overdue(): static {
		return $this->state(fn (array $attributes): array => [
			'due_date'	=> fake()->dateTimeBetween('-1 month', '-1 day'),
			'status'	=> fake()->randomElement(['todo', 'in_progress']),
		]);
	}

	public function highPriority(): static {
		return $this->state(fn (array $attributes): array => [
			'priority' => fake()->randomElement([TaskPriority::HIGH, TaskPriority::HIGHEST])->value,
		]);
	}

	public function lowPriority(): static {
		return $this->state(fn (array $attributes): array => [
			'priority' => fake()->randomElement([TaskPriority::LOW, TaskPriority::LOWEST])->value,
		]);
	}
}
