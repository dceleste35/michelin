<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string $position
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
     * The owner of this tire mount.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The catalog product mounted.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
