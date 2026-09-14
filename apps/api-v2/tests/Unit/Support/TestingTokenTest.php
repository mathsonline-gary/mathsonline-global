<?php

use App\Models\Brand;
use App\Support\TestingToken;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

// A TestCase, but no RefreshDatabase: the encrypter comes from the container, so the application
// has to boot, and nothing here touches the database. tests/Pest.php binds neither to Unit.
uses(TestCase::class);

/**
 * The brand argument verification ignores — a token is not scoped to one.
 */
function anyBrand(): Brand
{
    return new Brand;
}

/**
 * Encrypt an arbitrary payload the way a token is encrypted, to forge a shape verification rejects.
 */
function tokenCarrying(string $payload): string
{
    return Crypt::encryptString($payload);
}

test('a freshly generated token verifies', function (int $ttl) {
    expect(TestingToken::verify(anyBrand(), TestingToken::generate($ttl)))->toBeTrue();
})->with([
    'the default hour' => 3600,
    'a minute' => 60,
    'this very second' => 0,
    'a year' => 31_536_000,
]);

test('a token stops verifying once its expiry has passed', function (int $ttl) {
    expect(TestingToken::verify(anyBrand(), TestingToken::generate($ttl)))->toBeFalse();
})->with([
    'expired a second ago' => -1,
    'expired an hour ago' => -3600,
]);

test('anything that is not a token verifies false rather than throwing', function (?string $token) {
    expect(TestingToken::verify(anyBrand(), $token))->toBeFalse();
})->with([
    'nothing at all' => fn () => null,
    'an empty string' => fn () => '',
    'plain text' => fn () => 'whatever',
    'a token with a character appended' => fn () => TestingToken::generate().'x',
    'a token truncated' => fn () => substr(TestingToken::generate(), 0, -4),
    'ciphertext from another key' => fn () => (new Encrypter(random_bytes(32), 'AES-256-CBC'))
        ->encryptString((string) json_encode(['exp' => time() + 3600])),
    'a payload that is not JSON' => fn () => tokenCarrying('not json'),
    'a JSON payload that is not an object' => fn () => tokenCarrying('[1, 2, 3]'),
    'a payload carrying no expiry' => fn () => tokenCarrying('{"iss":"someone"}'),
    'an expiry that is a string' => fn () => tokenCarrying('{"exp":"'.(time() + 3600).'"}'),
    'an expiry that is null' => fn () => tokenCarrying('{"exp":null}'),
]);

test('two tokens minted from the same ttl differ', function () {
    // The encrypter carries a random IV, so a token is never a stable string to recognise or cache.
    expect(TestingToken::generate())->not->toBe(TestingToken::generate());
});
