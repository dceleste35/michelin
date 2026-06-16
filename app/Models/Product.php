<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $global_id
 * @property string $web_range_name
 * @property string|null $segment
 * @property int|null $width_etrto
 * @property int|null $diameter_etrto
 * @property int|null $tpi
 * @property string|null $min_pressure_bar
 * @property string|null $max_pressure_bar
 * @property string|null $rubber_tech
 * @property string|null $casing_tech
 * @property string|null $reinforcement_tech
 * @property string|null $ebike_tech
 * @property mixed $terrain_types
 * @property string|null $use
 * @property int|null $expected_life_km
 * @property string|null $rolling_resistance_watts
 * @property int|null $weight_g
 * @property string|null $ean_code
 * @property string|null $price_eur
 * @property string|null $image_url
 */
#[Fillable([
    'global_id',
    'web_range_name',
    'segment',
    'width_etrto',
    'diameter_etrto',
    'tpi',
    'min_pressure_bar',
    'max_pressure_bar',
    'rubber_tech',
    'casing_tech',
    'reinforcement_tech',
    'ebike_tech',
    'terrain_types',
    'use',
    'expected_life_km',
    'rolling_resistance_watts',
    'weight_g',
    'ean_code',
    'price_eur',
    'image_url',
])]
class Product extends Model
{
    /**
     * The tire mounts referencing this product.
     *
     * @return HasMany<UserTire, $this>
     */
    public function userTires(): HasMany
    {
        return $this->hasMany(UserTire::class);
    }
}
