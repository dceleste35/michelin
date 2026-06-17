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
 */
#[Fillable([
    'user_id',
    'product_id',
    'position',
    'mounted_at',
    'mounted_odometer_km',
    'wear_percent',
    'is_active',
])]
class UserTire extends Model
{
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
}
