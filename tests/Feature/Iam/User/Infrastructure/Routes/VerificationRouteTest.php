<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Src\Iam\User\Domain\ValueObjects\UserId;

use function Pest\Laravel\get;

test('registers the email verification route', function (): void {
    expect(Route::has('iam.verification.verify'))->toBeTrue();
});

test('rejects an unsigned verification URL', function (): void {
    $url = route('iam.verification.verify', [
        'userId' => UserId::generate()->value(),
        'token' => str_repeat('a', 64),
    ]);

    get($url)->assertForbidden();
});

test('rejects an expired signed verification URL', function (): void {
    $url = URL::temporarySignedRoute(
        'iam.verification.verify',
        now()->subMinute(),
        [
            'userId' => UserId::generate()->value(),
            'token' => str_repeat('a', 64),
        ],
    );

    get($url)->assertForbidden();
});
