<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $external_id
 * @property string $sport_type
 * @property int $distance_m
 * @property int $moving_time_s
 * @property string $average_speed_ms
 * @property int $total_elevation_gain_m
 * @property int|null $average_watts
 * @property int|null $average_cadence
 * @property string|null $surface
 * @property Carbon $start_date
 * @property mixed $raw_json
 */
#[Fillable([
    'user_id',
    'external_id',
    'sport_type',
    'distance_m',
    'moving_time_s',
    'average_speed_ms',
    'total_elevation_gain_m',
    'average_watts',
    'average_cadence',
    'surface',
    'start_date',
    'raw_json',
])]
class StravaActivity extends Model
{
    /**
     * The user who recorded this activity.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
