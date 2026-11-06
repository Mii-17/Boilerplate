<?php

declare(strict_types=1);

use Src\Iam\User\Application\Ports\HasherInterface;
use Src\Iam\User\Domain\ValueObjects\PasswordAttempt;
use Src\Iam\User\Domain\ValueObjects\PlainPassword;
use Src\Iam\User\Infrastructure\Crypt\LaravelHasherAdapter;

function laravelHasherAdapter(): LaravelHasherAdapter
{
    return app(LaravelHasherAdapter::class);
}

test('hashes PlainPassword into a different value than the plain text', function (): void {
    $plainPassword = PlainPassword::fromString('Secret123!');
    $laravelHasherAdapter = laravelHasherAdapter();
    $hashedPassword = $laravelHasherAdapter->hash($plainPassword);

    expect($hashedPassword->value())
        ->not->toBe($plainPassword->value());
});

test('verify returns true if password is correct and false if it is not', function (
    string $password,
    bool $expected
): void {
    $laravelHasherAdapter = laravelHasherAdapter();

    $plainPassword = PlainPassword::fromString('Secret123!');
    $hashedPassword = $laravelHasherAdapter->hash($plainPassword);

    $attempt = PasswordAttempt::fromString($password);

    expect($laravelHasherAdapter->verify($attempt, $hashedPassword))
        ->toBe($expected);
})->with([
    'correct password' => ['Secret123!', true],
    'incorrect password' => ['Wrong123!', false],
]);

test('resolves HasherInterface to LaravelHasherAdapter from container', function (): void {
    expect(app(HasherInterface::class))->toBeInstanceOf(LaravelHasherAdapter::class);
});
