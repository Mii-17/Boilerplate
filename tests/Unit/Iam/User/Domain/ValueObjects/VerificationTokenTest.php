<?php

declare(strict_types=1);

use Src\Iam\User\Domain\ValueObjects\VerificationToken;

test('verification token is not expired when created fresh', function (): void {
    $token = VerificationToken::generate();

    expect($token->value())->toBeString()
        ->and($token->isExpired())->toBeFalse();
});

test('verification token is expired when expiration time is in the past', function (): void {
    $token = new VerificationToken(
        value: 'some-random-token-string',
        expiresAt: new DateTimeImmutable('-1 hour')
    );

    expect($token->isExpired())->toBeTrue();
});

test('verification tokens are equal if their values match', function (): void {
    $expiresAt = new DateTimeImmutable('+24 hours');
    $token1 = new VerificationToken('token-abc', $expiresAt);
    $token2 = new VerificationToken('token-abc', $expiresAt);
    $token3 = new VerificationToken('token-xyz', $expiresAt);

    expect($token1->equals($token2))->toBeTrue()
        ->and($token1->equals($token3))->toBeFalse();
});
