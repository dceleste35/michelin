<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RidingStyle;
use App\Enums\Segment;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $strava_athlete_id
 * @property int|null $weight_kg
 * @property Segment|null $segment
 * @property bool $segment_overridden
 * @property RidingStyle|null $riding_style
 * @property Carbon|null $profile_confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'strava_athlete_id', 'weight_kg', 'segment', 'segment_overridden', 'riding_style'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Valeurs par défaut des attributs, alignées sur les valeurs par défaut de la base de données.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'segment_overridden' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'segment' => Segment::class,
            'riding_style' => RidingStyle::class,
            'segment_overridden' => 'boolean',
            'profile_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Les activités Strava de l'utilisateur.
     *
     * @return HasMany<StravaActivity, $this>
     */
    public function stravaActivities(): HasMany
    {
        return $this->hasMany(StravaActivity::class);
    }

    /**
     * Les pneus montés de l'utilisateur.
     *
     * @return HasMany<UserTire, $this>
     */
    public function tires(): HasMany
    {
        return $this->hasMany(UserTire::class);
    }

    /**
     * Restreint la requête aux utilisateurs connectés à Strava.
     *
     * @param  Builder<User>  $query
     */
    public function scopeWithStravaConnected(Builder $query): void
    {
        $query->whereNotNull('strava_athlete_id');
    }

    /**
     * Nombre de pneus en fin de vie à racheter (non archivés) — pour le badge « panier ».
     */
    public function reorderCount(): int
    {
        return $this->tires()->notArchived()->endOfLife()->notOrdered()->count();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
