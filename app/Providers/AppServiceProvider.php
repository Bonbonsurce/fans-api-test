<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansClient;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansClientInterface;
use Src\Infrastructure\Integrations\OnlyFans\ScriptedOnlyFansClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            OnlyFansClientInterface::class,
            fn ($app) => $app->make($this->onlyFansClientClass()),
        );
    }

    /**
     * @return class-string<OnlyFansClientInterface>
     */
    private function onlyFansClientClass(): string
    {
        return config('services.onlyfans.workload')
            ? ScriptedOnlyFansClient::class
            : OnlyFansClient::class;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
