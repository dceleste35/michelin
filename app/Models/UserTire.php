<?php

namespace App\Models;

use App\Enums\TirePosition;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property TirePosition $position
 * @property Carbon|null $mounted_at
 * @property int|null $mounted_odometer_km
 * @property string|null $wear_percent
 * @property bool $is_active
 * @property Carbon|null $archived_at
 */
#[Fillable([
    'user_id',
    'product_id',
    'position',
    'mounted_at',
    'mounted_odometer_km',
    'wear_percent',
    'is_active',
    'archived_at',
])]
class UserTire extends Model
{
    /**
     * Seuil d'usure (%) à partir duquel un pneu est considéré en fin de vie (à racheter).
     */
    public const END_OF_LIFE_WEAR = 80;

    /**
     * Récupère les attributs qui doivent être castés.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => TirePosition::class,
            'mounted_at' => 'date',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Le propriétaire de ce montage de pneu.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le produit du catalogue monté.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Limite la requête aux pneus actifs (actuellement montés).
     *
     * @param  Builder<UserTire>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Limite la requête aux pneus non archivés (collection courante).
     *
     * @param  Builder<UserTire>  $query
     */
    public function scopeNotArchived(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Limite la requête aux pneus archivés (rangés, conservés pour l'historique).
     *
     * @param  Builder<UserTire>  $query
     */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    /**
     * Limite la requête aux pneus en fin de vie (usure ≥ seuil) — à racheter.
     *
     * @param  Builder<UserTire>  $query
     */
    public function scopeEndOfLife(Builder $query): void
    {
        $query->where('wear_percent', '>=', self::END_OF_LIFE_WEAR);
    }
}
