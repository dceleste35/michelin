<?php

use App\Support\QrCode;

it('serves the QR gate page at /qr', function () {
    $this->get('/qr')
        ->assertOk()
        ->assertSee('data-test="qr-gate"', false)            // bloc QR desktop
        ->assertSee('An experience designed for mobile')
        ->assertSee('Open the app');                          // accès direct mobile
});

it('keeps the landing app at / without the QR gate', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Connect with Strava')
        ->assertDontSee('data-test="qr-gate"', false);
});

it('generates an inline QR svg without the xml prolog', function () {
    $svg = QrCode::svg('https://michelin.test');

    expect($svg)->toStartWith('<svg')->toContain('</svg>');
});
