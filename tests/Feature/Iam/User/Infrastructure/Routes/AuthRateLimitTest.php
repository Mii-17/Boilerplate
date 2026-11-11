<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

use function Pest\Laravel\get;

beforeEach(function (): void {
    Route::middleware('throttle:auth')
        ->get('/_test/auth-limited', fn () => response()->noContent());
});

test('allows requests within the auth rate limit', function (): void {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        get('/_test/auth-limited')->assertNoContent();
    }
});

test('blocks requests exceeding the auth rate limit', function (): void {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        get('/_test/auth-limited')->assertNoContent();
    }

    $response = get('/_test/auth-limited');

    $response->assertStatus(429);

    expect($response->headers->has('Retry-After'))->toBeTrue();
});

test('rate limit is scoped per client', function (): void {
    /** @var TestCase $test */
    $test = $this;

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $test->withServerVariables([
            'REMOTE_ADDR' => '10.0.0.1',
        ]);

        $test->get('/_test/auth-limited')->assertNoContent();
    }

    $test->withServerVariables([
        'REMOTE_ADDR' => '10.0.0.2',
    ]);

    $test->get('/_test/auth-limited')->assertNoContent();
});
