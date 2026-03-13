<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        if (app()->environment('local') && auth()->guest()) {
            try {
                if ($user = User::first()) {
                    auth()->login($user);
                }
            } catch (\Throwable) {
                // Database may not exist yet (e.g. during CI or fresh install).
            }
        }

        Gate::define('can-import-recipes', fn (User $user) => true);

        $this->configureDefaults();

        View::composer('app', function ($view) {
            $user = auth()->user();
            $theme = $user?->theme ?? 'default';
            $appearance = request()->cookie('appearance', 'system');

            $view->with([
                'theme' => $theme,
                'appearance' => $appearance,
            ]);
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
