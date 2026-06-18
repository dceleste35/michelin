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
 * @property float|null $wear_percent
 * @property float $baseline_wear_km
 * @property bool $is_active
 * @property Carbon|null $archived_at
 * @property Carbon|null $ordered_at
 */
#[Fillable([
    'user_id',
    'product_id',
    'position',
    'mounted_at',
    'mounted_odometer_km',
    'wear_percent',
    'baseline_wear_km',
    'is_active',
    'archived_at',
    'ordered_at',
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
            'ordered_at' => 'datetime',
            'wear_percent' => 'float',
            'baseline_wear_km' => 'float',
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
     * Recalcule et persiste l'usure (SCORE déterministe) : usure = (km de départ +
     * km des sorties associées à ce pneu) / durée de vie du produit, bornée à 100 %.
     */
    public function recomputeWear(): void
    {
        $ridesKm = (float) StravaActivity::forUserTire($this)->sum('distance_m') / 1000;
        $expectedLifeKm = (int) ($this->product?->expected_life_km ?: 4000);
        $totalKm = (float) $this->baseline_wear_km + $ridesKm;

        $this->wear_percent = round(min(100.0, $totalKm / $expectedLifeKm * 100), 1);
        $this->save();
    }

    /**
     * Cale le km de départ pour que l'usure atteigne le pourcentage cible, en tenant
     * compte des km déjà associés (levier de démo). Les sorties ajoutées ensuite font
     * remonter l'usure au-delà de la cible.
     */
    public function calibrateWearTo(float $targetPercent): void
    {
        $this->loadMissing('product');
        $expectedLifeKm = (int) ($this->product?->expected_life_km ?: 4000);
        $ridesKm = (float) StravaActivity::forUserTire($this)->sum('distance_m') / 1000;

        $this->baseline_wear_km = max(0.0, $targetPercent / 100 * $expectedLifeKm - $ridesKm);
        $this->recomputeWear();
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

    /**
     * Limite la requête aux pneus dont le remplacement n'a pas été commandé (alerte active).
     *
     * @param  Builder<UserTire>  $query
     */
    public function scopeNotOrdered(Builder $query): void
    {
        $query->whereNull('ordered_at');
    }

    /**
     * Limite la requête aux pneus dont le remplacement a été commandé.
     *
     * @param  Builder<UserTire>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->whereNotNull('ordered_at');
    }
}
