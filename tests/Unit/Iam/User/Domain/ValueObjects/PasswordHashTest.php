<?php

declare(strict_types=1);

use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidPasswordHashException;
use Src\Iam\User\Domain\ValueObjects\PasswordHash;

test('password hash can be instantiated from an existing hash string', function (): void {
    $rawHash = '$2y$12$pcy94dDKAJZR8X6b2iNiIu9nPEbBG7l7fioquPCXWP9ryp4F5Xv/6';

    $passwordHash = PasswordHash::fromHash($rawHash);

    expect($passwordHash->value())->toBe($rawHash)
        ->and((string) $passwordHash)->toBe($rawHash);
});

test('password hash cannot be empty', function (): void {
    expect(fn (): PasswordHash => PasswordHash::fromHash('   '))
        ->toThrow(InvalidPasswordHashException::class);
});

test('password hashes are equal if their values match', function (): void {
    $rawHash = '$2y$12$pcy94dDKAJZR8X6b2iNiIu9nPEbBG7l7fioquPCXWP9ryp4F5Xv/6';
    $otherHash = '$2y$12$V6f1PcFCOdIC91H.KUt0VepKI8LKEGGbKnYSWtlWG/JoV1saLKVQW';

    $hash1 = PasswordHash::fromHash($rawHash);
    $hash2 = PasswordHash::fromHash($rawHash);
    $hash3 = PasswordHash::fromHash($otherHash);

    expect($hash1->equals($hash2))->toBeTrue()
        ->and($hash1->equals($hash3))->toBeFalse();
});
