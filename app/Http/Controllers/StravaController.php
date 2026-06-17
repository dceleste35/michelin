<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StravaController extends Controller
{
    /**
     * Seeded hero profile the simulated connection signs in as.
     */
    private const DEMO_EMAIL = 'marc@rideready.test';

    /**
     * Simulated "Connect with Strava" (prototype).
     *
     * We do NOT call Strava: real accounts have no usable ride history, so the
     * connection is faked — we sign in as the seeded hero (Marc) whose
     * representative mock rides are what the app analyses, and show a short
     * Strava-style interstitial before landing on the dashboard.
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

        return view('strava.connecting');
    }
}
