<?php

namespace App\Support;

use App\Models\Brand;

/**
 * A computed token asking for a brand's testing plans.
 */
final class TestingToken
{
    /**
     * Verify a testing token for a brand.
     *
     * Unimplemented. Membership has no token at all — `?testing=1` was the whole check, which is
     * not something a public endpoint can honour — so there is nothing to port and the scheme is
     * undecided. Until it is, no token verifies and testing plans are never served, which the
     * description already permits: a token that does not apply is ignored, never rejected.
     *
     * The brand is the parameter rather than a secret, so an implementation can reach a column
     * without changing this signature.
     */
    public static function verify(Brand $brand, ?string $token): bool
    {
        return false;
    }
}
