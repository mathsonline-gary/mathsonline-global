<?php

use App\Models\Brand;
use App\Support\TestingToken;
use Illuminate\Support\Facades\Artisan;

/**
 * Run the command and return what it printed.
 */
function mintedToken(?int $ttl = null): string
{
    Artisan::call('testing-token:generate', $ttl === null ? [] : ['--ttl' => $ttl]);

    return trim(Artisan::output());
}

test('the command prints a token that verifies', function () {
    expect(TestingToken::verify(Brand::factory()->make(), mintedToken()))->toBeTrue();
});

test('the command honours a ttl', function () {
    expect(TestingToken::verify(Brand::factory()->make(), mintedToken(60)))->toBeTrue();
    expect(TestingToken::verify(Brand::factory()->make(), mintedToken(-1)))->toBeFalse();
});

test('the command runs in production', function () {
    // Production is the environment worth minting a token for, so there is deliberately no guard.
    $this->app->detectEnvironment(fn () => 'production');

    expect(TestingToken::verify(Brand::factory()->make(), mintedToken()))->toBeTrue();
});
