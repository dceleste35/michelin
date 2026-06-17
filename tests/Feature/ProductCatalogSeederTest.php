<?php

use App\Models\Product;
use Database\Seeders\ProductCatalogSeeder;

it('seeds the curated catalog sample across all segments', function () {
    $this->seed(ProductCatalogSeeder::class);

    expect(Product::count())->toBe(13)
        ->and(Product::distinct()->pluck('segment')->sort()->values()->all())
        ->toBe(['EBIKE_URBAN', 'GRAVEL', 'MTB', 'ROAD']);
});

it('seeds the demo gravel pair with coherent ETRTO for the upsell', function () {
    $this->seed(ProductCatalogSeeder::class);

    $powerGravel = Product::where('web_range_name', 'Power Gravel')->sole();
    $powerGravelRs = Product::where('web_range_name', 'Power Gravel RS')->sole();

    expect($powerGravel->segment)->toBe('GRAVEL')
        ->and($powerGravel->ean_code)->toBe('3528702637890')
        ->and($powerGravel->tpi)->toBe(120)
        ->and($powerGravel->terrain_types)->toBeArray()
        ->and($powerGravel->terrain_types)->toContain('OFFROAD MIXED')
        // Même diamètre de roue (622) → le Power Gravel RS est une montée en gamme cohérente.
        ->and($powerGravelRs->diameter_etrto)->toBe($powerGravel->diameter_etrto)
        ->and($powerGravelRs->diameter_etrto)->toBe(622)
        // Le RS est l'option course, plus légère et plus rapide.
        ->and($powerGravelRs->weight_g)->toBeLessThan($powerGravel->weight_g)
        ->and((float) $powerGravelRs->rolling_resistance_watts)
        ->toBeLessThan((float) $powerGravel->rolling_resistance_watts);
});

it('stores nullable specs cleanly (missing min pressure stays null)', function () {
    $this->seed(ProductCatalogSeeder::class);

    $rs = Product::where('web_range_name', 'Power Gravel RS')->sole();

    expect($rs->min_pressure_bar)->toBeNull()
        ->and((float) $rs->max_pressure_bar)->toBe(4.5);
});

it('is idempotent — reseeding does not duplicate rows', function () {
    $this->seed(ProductCatalogSeeder::class);
    $this->seed(ProductCatalogSeeder::class);

    expect(Product::count())->toBe(13)
        ->and(Product::where('ean_code', '3528705648480')->count())->toBe(1);
});
