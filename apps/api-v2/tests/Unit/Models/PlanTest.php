<?php

use App\Models\Plan;

// No factory and no TestCase: tests/Pest.php binds those to Feature only, and the accessor under
// test is arithmetic over three attributes, so it needs neither the container nor a database.
test('a plan runs for its billing period count plus the free extra count', function (string $interval, int $count, int $extra, int $days) {
    $plan = new Plan([
        'billing_period_interval' => $interval,
        'billing_period_count' => $count,
        'billing_period_extra_count' => $extra,
    ]);

    expect($plan->period_in_days)->toBe($days);
})->with([
    'a month' => ['month', 1, 0, 30],
    'six months' => ['month', 6, 0, 180],
    'a year of months' => ['month', 12, 0, 360],
    'twelve months plus six free' => ['month', 12, 6, 540],
    'a year' => ['year', 1, 0, 365],
    'a week' => ['week', 1, 0, 7],
    'a day' => ['day', 1, 0, 1],
]);
