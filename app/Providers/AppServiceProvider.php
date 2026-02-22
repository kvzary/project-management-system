<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Task;
use App\Observers\CommentObserver;
use App\Observers\TaskObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Task::observe(TaskObserver::class);
        Comment::observe(CommentObserver::class);

        // Use Filament's password reset page instead of the default Laravel route
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return route('filament.admin.auth.password-reset.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }
}
