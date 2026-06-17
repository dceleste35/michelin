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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'surface_derived' => Surface::class,
            'raw_json' => 'array',
            'start_date' => 'datetime',
        ];
    }

    /**
     * The user who recorded this activity.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Limit the query to activities from the last six months.
     *
     * @param  Builder<StravaActivity>  $query
     */
    public function scopeLastSixMonths(Builder $query): void
    {
        $query->where('start_date', '>=', now()->subMonths(6));
    }
}
