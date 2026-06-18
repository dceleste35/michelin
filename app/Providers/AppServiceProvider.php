<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;
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
        $this->configureDefaults();
    }

    /**
     * Configure les comportements par défaut pour une application prête pour la production.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // En production (derrière le proxy TLS Laravel Cloud), on force https pour des URLs
        // propres (QR, liens, assets) sans dépendre de la détection du schéma.
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        // Formatage des nombres selon la locale (FR en app, EN en tests) — suit app.locale.
        Number::useLocale(app()->getLocale());

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
