<?php

declare(strict_types=1);

use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidPlainPasswordException;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;

test('user password is a valid password', function (string $password): void {
    $userPassword = PlainPassword::fromString($password);

    expect($userPassword->value())->toBeString()
        ->and($userPassword->value())->toBe($password)
        ->and((string) $userPassword)->toBe($password);
})->with([
    'normal string' => 'Secret123!',
    'with space at the beginning' => ' Secret123!',
    'with space at the end' => 'Secret123! ',
]);

test('user password throws an exception when the value is not a valid password', function (string $invalidPassword): void {
    expect(fn (): PlainPassword => PlainPassword::fromString($invalidPassword))->toThrow(InvalidPlainPasswordException::class);
})->with([
    'blank string' => '',
    'only spaces' => '   ',
    'too short' => 'Se3!',
    'too long' => 'Secret123!901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234',
    'without uppercase' => 'secret123!',
    'without lowercase' => 'SECRET123!',
    'without number' => 'VerySecret!',
    'without symbol' => 'Secret123',
]);

test('user password is equal to another user password', function (): void {
    $password = 'Secret123!';
    $userPassword1 = PlainPassword::fromString($password);
    $userPassword2 = PlainPassword::fromString($password);
    $userPassword3 = PlainPassword::fromString('OtherSecret123!');

    expect($userPassword1->equals($userPassword2))->toBeTrue()
        ->and($userPassword1->equals($userPassword3))->toBeFalse();
});
