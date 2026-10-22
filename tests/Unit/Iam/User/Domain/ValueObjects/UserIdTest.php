<?php

declare(strict_types=1);

use Ramsey\Uuid\Uuid;
use Src\Iam\User\Domain\ValueObjects\UserId;

test('can generate a valid uuid v7 userId', function (): void {
    $userId = UserId::generate();
    $value = $userId->value();

    expect(Uuid::isValid($value))->toBeTrue()
        ->and(Uuid::fromString($value)->getVersion())->toBe(7);
});

test('can instantiate user id from a valid uuid string', function (): void {
    $uuid = Uuid::uuid7()->toString();
    $userId = UserId::fromString($uuid);
    expect($userId->value())->toBe($uuid);
});

test('user id throws exception when invalid', function (): void {
    expect(fn (): UserId => UserId::fromString('invalid-uuid'))
        ->toThrow(InvalidArgumentException::class);
});

test('user id value returns the value', function (): void {
    $uuid = Uuid::uuid7()->toString();
    $userId = UserId::fromString($uuid);
    expect($userId->value())->toBe($uuid);
});

test('user ids can be compared for equality', function (): void {
    $uuid = Uuid::uuid7()->toString();
    $id1 = UserId::fromString($uuid);
    $id2 = UserId::fromString($uuid);
    $id3 = UserId::generate();

    expect($id1->equals($id2))->toBeTrue()
        ->and($id1->equals($id3))->toBeFalse();
});
