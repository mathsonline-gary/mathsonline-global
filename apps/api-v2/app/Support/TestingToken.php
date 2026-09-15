<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * A computed token asking for a brand's testing plans.
 *
 * The token is the application key encrypting `{"exp": <unix timestamp>}` — no new column, no
 * per-brand secret, and forging one means forging `APP_KEY`. It is not brand-scoped: which brands
 * honour a token at all is the `testing_plans_enabled` flag, checked by the caller.
 */
final class TestingToken
{
    /**
     * Mint a testing token valid for the given number of seconds.
     */
    public static function generate(int $ttlSeconds = 3600): string
    {
        return Crypt::encryptString((string) json_encode(['exp' => time() + $ttlSeconds]));
    }

    /**
     * Verify a testing token.
     *
     * Nothing a caller sends is a reason to fail. A token that does not decrypt, does not decode,
     * carries no expiry or has passed it is simply not a token — ignored, never rejected.
     */
    public static function verify(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return false;
        }

        return is_array($payload)
            && is_int($payload['exp'] ?? null)
            && time() <= $payload['exp'];
    }
}
