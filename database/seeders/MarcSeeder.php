<?php

namespace Database\Seeders;

use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\StravaActivity;
use App\Models\User;
use App\Models\UserTire;
use App\Services\ProfileInferenceService;
use Carbon\CarbonImmutable;
use Database\Factories\StravaActivityFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Persona vedette : Marc (GRAVEL). ~80 activités GravelRide sur 6 mois, 60/40
 * asphalte/tout-terrain. Marc a changé de pneus à mi-saison : un ancien jeu Power
 * Gravel (retiré, 100 % usé) sur les vieilles sorties, et le jeu actuel (arrière 86 %)
 * sur les récentes — l'historique par sortie est conservé. Entièrement déterministe
 * pour que `demo:reset` reproduise l'état exact. Nécessite ProductCatalogSeeder.
 */
class MarcSeeder extends Seeder
{
    private const ACTIVITY_COUNT = 80;

    private const ATHLETE_ID = 42;

    private const ACTIVITY_BASE_ID = 15_000_000_000;

    private const GEAR_ID = 'b9100042'; // L'unique vélo gravel de Marc (gear id Strava)

    private const SWAP_INDEX = 40; // sorties 0–39 (récentes) = jeu actuel ; 40–79 (anciennes) = ancien jeu

    private const UNCONFIRMED_RECENT = 8; // les 8 sorties les plus récentes restent « à vérifier »

    public function run(): void
    {
        $marc = User::updateOrCreate(
            ['email' => 'marc@rideready.test'],
            [
                'name' => 'Marc',
                'password' => Hash::make('password'),
                'strava_athlete_id' => (string) self::ATHLETE_ID,
                'weight_kg' => 90,
                'segment' => Segment::Gravel,
                'riding_style' => RidingStyle::Endurance,
                'segment_overridden' => false,
            ],
        );

        $tires = $this->mountTires($marc);
        $this->seedActivities($marc, $tires);
    }

    /**
     * Crée la collection de pneus de Marc : le jeu actuel (actif) + l'ancien jeu (retiré).
     *
     * @return array{current: array<string, UserTire>, old: array<string, UserTire>}
     */
    private function mountTires(User $marc): array
    {
        $powerGravel = Product::where('web_range_name', 'Power Gravel')->sole();
        $now = CarbonImmutable::now();

        // Jeu actuel (actif) — calibré 86 % arrière / 72 % avant : déclenche l'alerte de démo.
        $current = [
            TirePosition::Rear->value => $this->tire($marc, $powerGravel, TirePosition::Rear, $now->subDays(90), 4200, 86.0, true),
            TirePosition::Front->value => $this->tire($marc, $powerGravel, TirePosition::Front, $now->subDays(90), 4200, 72.0, true),
        ];

        // Ancien jeu (retiré il y a ~90 j, usé à 100 % puis remplacé) — garde son historique.
        $old = [
            TirePosition::Rear->value => $this->tire($marc, $powerGravel, TirePosition::Rear, $now->subDays(180), 0, 100.0, false),
            TirePosition::Front->value => $this->tire($marc, $powerGravel, TirePosition::Front, $now->subDays(180), 0, 100.0, false),
        ];

        return ['current' => $current, 'old' => $old];
    }

    /**
     * Crée (ou met à jour) un pneu monté, identifié par (utilisateur, produit, position, actif).
     */
    private function tire(User $marc, Product $product, TirePosition $position, CarbonImmutable $mountedAt, int $odometer, float $wear, bool $active): UserTire
    {
        return UserTire::updateOrCreate(
            ['user_id' => $marc->id, 'product_id' => $product->id, 'position' => $position, 'is_active' => $active],
            ['mounted_at' => $mountedAt->toDateString(), 'mounted_odometer_km' => $odometer, 'wear_percent' => $wear],
        );
    }

    /**
     * @param  array{current: array<string, UserTire>, old: array<string, UserTire>}  $tires
     */
    private function seedActivities(User $marc, array $tires): void
    {
        $now = CarbonImmutable::now();
        $inference = app(ProfileInferenceService::class);

        for ($i = 0; $i < self::ACTIVITY_COUNT; $i++) {
            // Profil de grimpe réaliste par sortie (m/km). À l'allure régulière de 26 km/h de Marc, ces
            // signaux SE DÉRIVENT en ~60 % asphalte / 20 % chemin damé / 20 % mixte — la surface
            // est calculée par deriveSurface(), jamais codée en dur (crédibilité auprès du jury).
            $elevationPerKm = match ($i % 5) {
                0, 1, 2 => 5,   // plat & rapide → asphalte (gravel à allure route)
                3 => 11,        // vallonné → chemin damé
                default => 18,  // accidenté → mixte
            };

            // Une longue sortie (~150 km) toutes les 12 sorties, sinon 35–75 km.
            $distanceKm = $i % 12 === 0 ? 150 : 35 + ($i * 7 % 41);
            $distanceM = $distanceKm * 1000;
            $avgSpeedMs = 7.2; // ~26 km/h, allure d'endurance régulière
            $externalId = (string) (self::ACTIVITY_BASE_ID + $i);

            // Swap : sorties récentes (i < 40) sur le jeu actuel, anciennes sur l'ancien jeu.
            $era = $i < self::SWAP_INDEX ? 'current' : 'old';

            $attributes = [
                'external_id' => $externalId,
                'gear_id' => self::GEAR_ID,
                'sport_type' => 'GravelRide',
                'distance_m' => $distanceM,
                'moving_time_s' => (int) round($distanceM / $avgSpeedMs),
                'average_speed_ms' => $avgSpeedMs,
                'total_elevation_gain_m' => $distanceKm * $elevationPerKm,
                'average_watts' => 178,
                'average_cadence' => 84,
                'start_date' => $now->subDays((int) round($i * 180 / self::ACTIVITY_COUNT))->setTime(7, 30),
            ];

            // La surface est DÉRIVÉE des signaux de la sortie via les règles documentées.
            $attributes['surface_derived'] = $inference->deriveSurface(new StravaActivity($attributes));
            // Pneus montés lors de la sortie (auto = jeu de l'époque). Les récentes restent à vérifier.
            $attributes['front_tire_id'] = $tires[$era][TirePosition::Front->value]->id;
            $attributes['rear_tire_id'] = $tires[$era][TirePosition::Rear->value]->id;
            $attributes['tires_confirmed'] = $i >= self::UNCONFIRMED_RECENT;
            $attributes['raw_json'] = StravaActivityFactory::stravaPayload($attributes, self::ATHLETE_ID);

            StravaActivity::updateOrCreate(
                ['user_id' => $marc->id, 'external_id' => $externalId],
                $attributes,
            );
        }
    }
}
