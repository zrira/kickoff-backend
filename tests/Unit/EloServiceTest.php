<?php

use App\Services\EloService;

it('calculates positive delta when winner has lower ELO', function () {
    $service = new EloService();

    $delta = $service->calculate(
        winnerAvgElo: 900.0,
        loserAvgElo: 1100.0,
        scoreDiff: 2
    );

    // Winner had lower ELO, should get more points
    expect($delta)->toBeGreaterThan(20);
});

it('calculates smaller delta when winner has higher ELO', function () {
    $service = new EloService();

    $delta = $service->calculate(
        winnerAvgElo: 1200.0,
        loserAvgElo: 800.0,
        scoreDiff: 1
    );

    // Winner had higher ELO, should get fewer base points
    expect($delta)->toBeLessThan(15);
});

it('awards bonus points for larger score margins', function () {
    $service = new EloService();

    $deltaSmall = $service->calculate(1000.0, 1000.0, 1);
    $deltaLarge = $service->calculate(1000.0, 1000.0, 5);

    expect($deltaLarge)->toBeGreaterThan($deltaSmall);
});

it('always returns at least 1 point', function () {
    $service = new EloService();

    $delta = $service->calculate(2000.0, 500.0, 0);

    expect($delta)->toBeGreaterThanOrEqual(1);
});

it('caps margin bonus', function () {
    $service = new EloService();

    $delta10 = $service->calculate(1000.0, 1000.0, 10);
    $delta20 = $service->calculate(1000.0, 1000.0, 20);

    // Both should have the same margin bonus (capped)
    expect($delta10)->toBe($delta20);
});
