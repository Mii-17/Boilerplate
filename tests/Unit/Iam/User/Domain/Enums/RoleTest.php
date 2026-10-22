<?php

declare(strict_types=1);

use Src\Iam\User\Domain\Enums\Role;

test('role enum has expected values', function (): void {
    expect(Role::USER->value)->toBe('user')
        ->and(Role::ADMIN->value)->toBe('admin');
});

test('role can be created from valid string value', function (): void {
    $role = Role::tryFrom('user');

    expect($role)->toBe(Role::USER);
});

test('role returns null when created from invalid string value', function (): void {
    $role = Role::tryFrom('super-admin-invalid');

    expect($role)->toBeNull();
});

test('role throws exception when using from with invalid value', function (): void {
    expect(fn (): Role => Role::from('invalid-role'))
        ->toThrow(ValueError::class);
});

test('role helper methods correctly identify user type', function (): void {
    $userRole = Role::USER;
    $adminRole = Role::ADMIN;

    expect($userRole->isUser())->toBeTrue()
        ->and($userRole->isAdmin())->toBeFalse()
        ->and($adminRole->isAdmin())->toBeTrue()
        ->and($adminRole->isUser())->toBeFalse();
});
