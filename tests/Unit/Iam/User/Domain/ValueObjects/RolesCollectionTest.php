<?php

declare(strict_types=1);

use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\ValueObjects\Exceptions\InvalidRolesCollectionException;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;

test('roles collection can be created with valid unique roles', function (): void {
    $roles = new RolesCollection([Role::USER, Role::ADMIN]);

    expect($roles->all())->toHaveCount(2)
        ->and($roles->has(Role::USER))->toBeTrue()
        ->and($roles->has(Role::ADMIN))->toBeTrue();
});

test('roles collection removes duplicate roles', function (): void {
    $roles = new RolesCollection([Role::USER, Role::USER, Role::ADMIN]);

    expect($roles->all())->toHaveCount(2);
});

test('roles collection throws exception when empty', function (): void {
    expect(fn (): RolesCollection => new RolesCollection([]))
        ->toThrow(InvalidRolesCollectionException::class, 'Roles collection cannot be empty.');
});

test('roles collection can check for admin role easily', function (): void {
    $userRoles = new RolesCollection([Role::USER]);
    $adminRoles = new RolesCollection([Role::USER, Role::ADMIN]);

    expect($userRoles->isAdmin())->toBeFalse()
        ->and($adminRoles->isAdmin())->toBeTrue();
});
