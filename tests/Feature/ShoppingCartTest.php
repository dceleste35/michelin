<?php

use App\Models\Product;
use Livewire\Livewire;

test('shopping cart component mounts and displays recommended products', function () {
    Product::create([
        'global_id' => 'BI-177',
        'web_range_name' => 'Power Gravel RS',
        'segment' => 'GRAVEL',
        'ean_code' => '3528705648480',
    ]);

    Livewire::test('shopping-cart')
        ->assertSee('Power Gravel RS')
        ->assertSee('Liquide Préventif Muc-Off')
        ->assertSee('Valves Tubeless Michelin');
});

test('shopping cart component updates price dynamically', function () {
    Product::create([
        'global_id' => 'BI-177',
        'web_range_name' => 'Power Gravel RS',
        'segment' => 'GRAVEL',
        'ean_code' => '3528705648480',
    ]);

    Livewire::test('shopping-cart')
        ->assertSet('tireQty', 2)
        ->assertSet('includeSealant', true)
        ->assertSet('includeValves', true)
        // Subtotal: 2 * 54.99 + 1 * 9.90 + 1 * 14.90 = 134.78
        ->assertSet('subtotal', 134.78)
        ->assertSet('total', 134.78)
        ->set('includeSealant', false)
        // Subtotal: 2 * 54.99 + 1 * 14.90 = 124.88
        ->assertSet('subtotal', 124.88)
        ->set('tireQty', 1)
        ->set('includeValves', false)
        // Subtotal: 1 * 54.99 = 54.99 -> Total = 54.99 + 4.90 = 59.89
        ->assertSet('subtotal', 54.99)
        ->assertSet('total', 59.89);
});

test('shopping cart checkout works', function () {
    Product::create([
        'global_id' => 'BI-177',
        'web_range_name' => 'Power Gravel RS',
        'segment' => 'GRAVEL',
        'ean_code' => '3528705648480',
    ]);

    Livewire::test('shopping-cart')
        ->call('checkout');
});
