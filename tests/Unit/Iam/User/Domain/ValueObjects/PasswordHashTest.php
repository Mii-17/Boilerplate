<?php

declare(strict_types=1);

use Src\Iam\User\Domain\ValueObjects\PasswordHash;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;

test('password hash can be created from plain password and verifies correctly', function (): void {
    $plainPassword = PlainPassword::fromString('Secret123!');
    $passwordHash = PasswordHash::fromPlainText($plainPassword);

    expect($passwordHash->value())->toBeString()
        ->and($passwordHash->verify($plainPassword))->toBeTrue()
        ->and($passwordHash->verify(PlainPassword::fromString('WrongSecret123!')))->toBeFalse();
});

test('password hash can be instantiated from an existing hash string', function (): void {
    $rawHash = password_hash('Secret123!', PASSWORD_ARGON2ID);
    $passwordHash = PasswordHash::fromHash($rawHash);

    expect($passwordHash->value())->toBe($rawHash)
        ->and((string) $passwordHash)->toBe($rawHash);
});

test('password hashes are equal if their values match', function (): void {
    $rawHash = password_hash('Secret123!', PASSWORD_ARGON2ID);
    $hash1 = PasswordHash::fromHash($rawHash);
    $hash2 = PasswordHash::fromHash($rawHash);
    $hash3 = PasswordHash::fromHash(password_hash('OtherSecret123!', PASSWORD_ARGON2ID));

    expect($hash1->equals($hash2))->toBeTrue()
        ->and($hash1->equals($hash3))->toBeFalse();
});
