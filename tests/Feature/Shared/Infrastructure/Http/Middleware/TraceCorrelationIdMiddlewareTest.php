<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Context;

use function Pest\Laravel\get;
use function Pest\Laravel\withHeader;

beforeEach(function (): void {
    Context::forget('correlation_id');
});

test('uses incoming correlation ID when it is a valid UUID', function (): void {
    $correlationId = '01925a8c-1b3a-7d9e-8f2a-123456789abc';

    $response = withHeader('X-Correlation-ID', $correlationId)
        ->get('/up');

    $response->assertOk();

    expect($response->headers->get('X-Correlation-ID'))
        ->toBe($correlationId);
});

test('generates a new UUID when incoming correlation ID is invalid', function (): void {
    $response = withHeader('X-Correlation-ID', 'not-a-uuid')
        ->get('/up');

    $response->assertOk();

    $header = $response->headers->get('X-Correlation-ID');

    expect($header)
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

test('generates a new UUID when correlation ID header is missing', function (): void {
    $response = get('/up');

    $response->assertOk();

    $header = $response->headers->get('X-Correlation-ID');

    expect($header)
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

test('adds correlation ID to response headers', function (): void {
    $response = get('/up');

    expect($response->headers->has('X-Correlation-ID'))->toBeTrue();
});

test('adds correlation ID to error responses', function (): void {
    $response = get('/api/nonexistent');

    expect($response->getStatusCode())->toBe(404)
        ->and($response->headers->has('X-Correlation-ID'))->toBeTrue();
});

test('adds correlation ID to Laravel context', function (): void {
    $correlationId = '01925a8c-1b3a-7d9e-8f2a-123456789abc';

    withHeader('X-Correlation-ID', $correlationId)
        ->get('/up');

    expect(Context::get('correlation_id'))->toBe($correlationId);
});
