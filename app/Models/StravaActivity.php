<?php

namespace App\Models;

use App\Enums\Surface;
use Database\Factories\StravaActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $external_id
 * @property string $sport_type
 * @property string|null $gear_id
 * @property int|null $front_tire_id
 * @property int|null $rear_tire_id
 * @property bool $tires_confirmed
 * @property int $distance_m
 * @property int $moving_time_s
 * @property string $average_speed_ms
 * @property int $total_elevation_gain_m
 * @property int|null $average_watts
 * @property int|null $average_cadence
 * @property Surface|null $surface_derived
 * @property Carbon $start_date
 * @property array<string, mixed>|null $raw_json
 */
#[Fillable([
    'user_id',
    'external_id',
    'sport_type',
    'gear_id',
    'front_tire_id',
    'rear_tire_id',
    'tires_confirmed',
    'distance_m',
    'moving_time_s',
    'average_speed_ms',
    'total_elevation_gain_m',
    'average_watts',
    'average_cadence',
    'surface_derived',
    'start_date',
    'raw_json',
])]
class StravaActivity extends Model
{
    /** @use HasFactory<StravaActivityFactory> */
    use HasFactory;

    /**
     * Récupère les attributs qui doivent être castés.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'surface_derived' => Surface::class,
            'raw_json' => 'array',
            'start_date' => 'datetime',
            'tires_confirmed' => 'boolean',
        ];
    }

    /**
     * L'utilisateur qui a enregistré cette activité.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le pneu avant monté lors de cette sortie.
     *
     * @return BelongsTo<UserTire, $this>
     */
    public function frontTire(): BelongsTo
    {
        return $this->belongsTo(UserTire::class, 'front_tire_id');
    }

    /**
     * Le pneu arrière monté lors de cette sortie.
     *
     * @return BelongsTo<UserTire, $this>
     */
    public function rearTire(): BelongsTo
    {
        return $this->belongsTo(UserTire::class, 'rear_tire_id');
    }

    /**
     * Limite la requête aux activités des six derniers mois.
     *
     * @param  Builder<StravaActivity>  $query
     */
    public function scopeLastSixMonths(Builder $query): void
    {
        $query->where('start_date', '>=', now()->subMonths(6));
    }

    /**
     * Limite la requête aux sorties roulées sur un pneu donné (avant ou arrière).
     *
     * @param  Builder<StravaActivity>  $query
     */
    public function scopeForUserTire(Builder $query, UserTire $tire): void
    {
        $query->where(function (Builder $sub) use ($tire): void {
            $sub->where('front_tire_id', $tire->id)->orWhere('rear_tire_id', $tire->id);
        });
    }
}
