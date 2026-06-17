<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StravaController extends Controller
{
    /**
     * Profil héros initialisé sous lequel la connexion simulée s'authentifie.
     */
    private const DEMO_EMAIL = 'marc@rideready.test';

    /**
     * « Connect with Strava » simulé (prototype).
     *
     * Nous n'appelons PAS Strava : les comptes réels n'ont pas d'historique de
     * sorties exploitable, la connexion est donc factice — nous nous
     * authentifions sous le héros initialisé (Marc), dont les sorties fictives
     * représentatives sont celles que l'application analyse, et nous affichons
     * un court écran intermédiaire façon Strava. La première connexion mène à
     * l'onboarding aux valeurs par défaut intelligentes ; une fois le profil
     * confirmé, les connexions suivantes accèdent directement aux activités (le
     * profil reste modifiable depuis la barre latérale).
     */
    public function connect(): View|RedirectResponse
    {
        $marc = User::where('email', self::DEMO_EMAIL)->first();

        if ($marc === null) {
            return redirect()->route('login')->withErrors([
                'strava' => __('Demo profile is not seeded — run "php artisan demo:reset".'),
            ]);
        }

        Auth::login($marc);

        $target = $marc->profile_confirmed_at !== null
            ? route('activities')
            : route('profile');

        return view('strava.connecting', ['target' => $target]);
    }
}
