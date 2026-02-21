<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\SprintStatus;
use App\Enums\TaskPriority;
// TaskStatus enum no longer cast; use string values directly
use App\Enums\TaskType;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users (password for all: 'password')
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $manager = User::create([
            'name' => 'Project Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
        ]);

        $developer1 = User::create([
            'name' => 'John Developer',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
        ]);

        $developer2 = User::create([
            'name' => 'Jane Developer',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
        ]);

        $viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create projects
        $project1 = Project::create([
            'name' => 'E-Commerce Platform',
            'key' => 'ECOM',
            'description' => '<p>A comprehensive e-commerce platform with modern features including product catalog, shopping cart, checkout, and payment integration.</p>',
            'status' => ProjectStatus::ACTIVE,
            'owner_id' => $manager->id,
        ]);

        $project1->members()->attach([$developer1->id, $developer2->id, $manager->id]);

        $project2 = Project::create([
            'name' => 'Mobile App Development',
            'key' => 'MOBILE',
            'description' => '<p>Cross-platform mobile application development using React Native.</p>',
            'status' => ProjectStatus::ACTIVE,
            'owner_id' => $manager->id,
        ]);

        $project2->members()->attach([$developer1->id, $manager->id]);

        $project3 = Project::create([
            'name' => 'Legacy System Migration',
            'key' => 'LEGACY',
            'description' => '<p>Migration of legacy system to modern cloud infrastructure.</p>',
            'status' => ProjectStatus::ON_HOLD,
            'owner_id' => $admin->id,
        ]);

        // Create sprints for project 1
        $sprint1 = Sprint::create([
            'project_id' => $project1->id,
            'name' => 'Sprint 1 - Foundation',
            'goal' => '<p>Set up project foundation, authentication, and basic CRUD operations.</p>',
            'start_date' => now()->subDays(14)->toDateString(),
            'end_date' => now()->toDateString(),
            'status' => SprintStatus::COMPLETED,
        ]);

        $sprint2 = Sprint::create([
            'project_id' => $project1->id,
            'name' => 'Sprint 2 - Product Catalog',
            'goal' => '<p>Implement product catalog with categories, search, and filtering.</p>',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'status' => SprintStatus::PLANNING,
        ]);

        // Create sprints for project 2
        $sprint3 = Sprint::create([
            'project_id' => $project2->id,
            'name' => 'Sprint 1 - MVP',
            'goal' => '<p>Build minimum viable product with core features.</p>',
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'status' => SprintStatus::ACTIVE,
        ]);

        // Create tasks for project 1
        $task1 = Task::create([
            'project_id' => $project1->id,
            'sprint_id' => $sprint1->id,
            'title' => 'Set up Laravel project with Filament',
            'description' => '<p>Initialize Laravel 11 project and install Filament admin panel.</p>',
            'type' => TaskType::TASK,
            'status' => 'done',
            'priority' => TaskPriority::HIGHEST,
            'reporter_id' => $manager->id,
            'assigned_to' => $developer1->id,
            'story_points' => 5,
            'position' => 0,
        ]);

        $task2 = Task::create([
            'project_id' => $project1->id,
            'sprint_id' => $sprint1->id,
            'title' => 'Implement user authentication',
            'description' => '<p>Set up user registration, login, and password reset functionality.</p>',
            'type' => TaskType::STORY,
            'status' => 'done',
            'priority' => TaskPriority::HIGH,
            'reporter_id' => $manager->id,
            'assigned_to' => $developer2->id,
            'story_points' => 8,
            'position' => 1,
        ]);

        $task3 = Task::create([
            'project_id' => $project1->id,
            'sprint_id' => $sprint2->id,
            'title' => 'Design product catalog database schema',
            'description' => '<p>Create migrations for products, categories, and attributes tables.</p>',
            'type' => TaskType::TASK,
            'status' => 'in_progress',
            'priority' => TaskPriority::HIGH,
            'reporter_id' => $manager->id,
            'assigned_to' => $developer1->id,
            'story_points' => 5,
            'due_date' => now()->addDays(3),
            'position' => 2,
        ]);

        $task4 = Task::create([
            'project_id' => $project1->id,
            'sprint_id' => $sprint2->id,
            'title' => 'Fix product image upload bug',
            'description' => '<p>Product images are not being uploaded correctly. Need to fix the upload handler.</p>',
            'type' => TaskType::BUG,
            'status' => 'todo',
            'priority' => TaskPriority::HIGHEST,
            'reporter_id' => $developer2->id,
            'assigned_to' => $developer1->id,
            'story_points' => 3,
            'due_date' => now()->addDays(1),
            'position' => 3,
        ]);

        $task5 = Task::create([
            'project_id' => $project1->id,
            'sprint_id' => null,
            'title' => 'Payment Gateway Integration Epic',
            'description' => '<p>Integrate Stripe payment gateway for order processing.</p>',
            'type' => TaskType::EPIC,
            'status' => 'todo',
            'priority' => TaskPriority::MEDIUM,
            'reporter_id' => $manager->id,
            'assigned_to' => null,
            'story_points' => 21,
            'position' => 4,
        ]);

        // Create tasks for project 2
        $task6 = Task::create([
            'project_id' => $project2->id,
            'sprint_id' => $sprint3->id,
            'title' => 'Set up React Native project',
            'description' => '<p>Initialize React Native project with necessary dependencies.</p>',
            'type' => TaskType::TASK,
            'status' => 'done',
            'priority' => TaskPriority::HIGHEST,
            'reporter_id' => $manager->id,
            'assigned_to' => $developer1->id,
            'story_points' => 5,
            'position' => 0,
        ]);

        $task7 = Task::create([
            'project_id' => $project2->id,
            'sprint_id' => $sprint3->id,
            'title' => 'Implement navigation',
            'description' => '<p>Set up React Navigation with bottom tabs and stack navigation.</p>',
            'type' => TaskType::TASK,
            'status' => 'in_review',
            'priority' => TaskPriority::HIGH,
            'reporter_id' => $manager->id,
            'assigned_to' => $developer1->id,
            'story_points' => 8,
            'position' => 1,
        ]);

        // Create comments
        Comment::create([
            'commentable_type' => Task::class,
            'commentable_id' => $task3->id,
            'user_id' => $manager->id,
            'body' => '<p>Please make sure to include proper indexes for performance.</p>',
        ]);

        Comment::create([
            'commentable_type' => Task::class,
            'commentable_id' => $task3->id,
            'user_id' => $developer1->id,
            'body' => '<p>Will do! I\'m also adding foreign key constraints for data integrity.</p>',
        ]);

        Comment::create([
            'commentable_type' => Task::class,
            'commentable_id' => $task4->id,
            'user_id' => $developer2->id,
            'body' => '<p>This is blocking the product catalog feature. High priority!</p>',
        ]);

        Comment::create([
            'commentable_type' => Project::class,
            'commentable_id' => $project1->id,
            'user_id' => $manager->id,
            'body' => '<p>Great progress on Sprint 1! Let\'s keep the momentum going.</p>',
        ]);
    }
}
