<?php

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

test('the command prints a usable token, with the default ttl and with one given', function () {
    expect(TestingToken::verify(mintedToken()))->toBeTrue();
    expect(TestingToken::verify(mintedToken(60)))->toBeTrue();
});

test('the command runs in production', function () {
    // Production is the environment worth minting a token for, so there is deliberately no guard.
    $this->app->detectEnvironment(fn () => 'production');

    expect(TestingToken::verify(mintedToken()))->toBeTrue();
});
