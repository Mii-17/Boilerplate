<?php

declare(strict_types=1);

use Src\Shared\Domain\ValueObjects\Email;
use Src\Shared\Domain\ValueObjects\Exceptions\InvalidEmailException;

test('user email can be instantiated from a valid email', function (string $validEmail): void {
    $email = Email::fromString($validEmail);

    expect($email->value())->toBe(trim(strtolower($validEmail)));
})->with([
    'valid email' => 'valid-email@valid-domain.com',
    'email with whitespace on the left' => ' valid-email@valid-domain.com',
    'email with whitespace on the right' => 'valid-email@valid-domain.com ',
    'email with whitespace on both sides' => ' valid-email@valid-domain.com ',
    'email with uppercase letters' => 'Valid-Email@Valid-Domain.com',
    'email with + sign' => 'valid+email@valid-domain.com',
]);

test('user email throws exception when invalid', function (string $invalidEmail): void {
    expect(fn (): Email => Email::fromString($invalidEmail))
        ->toThrow(InvalidEmailException::class);
})->with([
    'invalid email' => 'invalid-email',
    'blank' => '',
    'whitespace' => ' ',
    'number' => '1234567890',
    'email without domain' => 'invalid-email@',
    'email without username' => '@invalid-domain.com',
    'email with double at' => 'invalid-email@@invalid-domain.com',
    'email with double dot' => 'invalid-email@.invalid-domain.com',
    'email with special chars' => 'invalid-email@invalid-domain.com/*!#$%^&*()_+{}:"?><~',
    'too long email' => str_repeat('a', 250).'@domain.com',
]);

test('user emails can be compared for equality', function (): void {
    $email1 = Email::fromString('valid-email@valid-domain.com');
    $email2 = Email::fromString('Valid-Email@valid-domain.com');
    $email3 = Email::fromString('another-valid-email@valid-domain.com');

    expect($email1->equals($email2))->toBeTrue()
        ->and($email1->equals($email3))->toBeFalse();
});

test('user email can be converted to string', function (): void {
    $email = Email::fromString('USER@example.com');

    expect((string) $email)->toBe('user@example.com');
});

test('invalid email exception includes the original email', function (): void {
    $email = ' invalid-email ';

    expect(fn (): Email => Email::fromString($email))
        ->toThrow(
            InvalidEmailException::class,
            'Invalid format for < invalid-email >.'
        );
});
