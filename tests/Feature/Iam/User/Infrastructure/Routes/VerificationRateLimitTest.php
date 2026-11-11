<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Src\Iam\User\Domain\ValueObjects\UserId;

use function Pest\Laravel\get;

function signedVerificationUrl(): string
{
    return URL::temporarySignedRoute(
        'iam.verification.verify',
        now()->addHour(),
        [
            'userId' => UserId::generate()->value(),
            'token' => str_repeat('a', 64),
        ],
    );
}

function unsignedVerificationUrl(): string
{
    return route('iam.verification.verify', [
        'userId' => UserId::generate()->value(),
        'token' => str_repeat('a', 64),
    ]);
}

test('allows verification attempts within the rate limit', function (): void {
    for ($attempt = 0; $attempt < 10; $attempt++) {
        get(signedVerificationUrl())->assertNoContent();
    }
});

test('blocks verification attempts exceeding the rate limit', function (): void {
    for ($attempt = 0; $attempt < 10; $attempt++) {
        get(signedVerificationUrl())->assertNoContent();
    }

    $response = get(signedVerificationUrl());

    $response->assertStatus(429);

    expect($response->headers->has('Retry-After'))->toBeTrue();
});

test('throttles invalid attempts before validating the signature', function (): void {
    for ($attempt = 0; $attempt < 10; $attempt++) {
        get(unsignedVerificationUrl())->assertForbidden();
    }

    get(unsignedVerificationUrl())->assertStatus(429);
});
