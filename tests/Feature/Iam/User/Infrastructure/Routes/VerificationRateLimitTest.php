<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Infrastructure\Models\UserModel;

use function Pest\Laravel\getJson;

function signedVerificationUrlWithWrongToken(): string
{
    $user = UserModel::factory()->unverified()->create();

    return URL::temporarySignedRoute(
        'iam.verification.verify',
        now()->addHour(),
        ['userId' => $user->id, 'token' => str_repeat('b', 64)],
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
    $url = signedVerificationUrlWithWrongToken();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        getJson($url)->assertUnprocessable();
    }
});

test('blocks verification attempts exceeding the rate limit', function (): void {
    $url = signedVerificationUrlWithWrongToken();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        getJson($url)->assertUnprocessable();
    }

    $response = getJson($url);

    $response->assertTooManyRequests();

    expect($response->headers->has('Retry-After'))->toBeTrue();
});

test('throttles invalid attempts before validating the signature', function (): void {
    $url = unsignedVerificationUrl();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        getJson($url)->assertForbidden();
    }

    getJson($url)->assertTooManyRequests();
});
