<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Infrastructure\Models\UserModel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withoutExceptionHandling;

beforeEach(function (): void {
    Route::middleware('role:admin')
        ->get('/_test/role-protected', fn () => response()->json(['success' => true]));
    Route::middleware('role:admin,user')
        ->get('/_test/role-everyone', fn () => response()->json(['success' => true]));
    Route::middleware('role:superadmin')
        ->get('/_test/role-unknown', fn () => response()->json(['success' => true]));
});

test('returns 401 when the user is unauthenticated', function (): void {
    getJson('/_test/role-protected')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('returns 403 when the user does not have the required role', function (): void {
    $user = UserModel::factory()
        ->withRole([Role::USER])
        ->create();

    Sanctum::actingAs($user);

    getJson('/_test/role-protected')
        ->assertForbidden()
        ->assertJson(['message' => 'Forbidden.']);
});

test('allows access when the user has the required admin role', function (): void {
    $user = UserModel::factory()
        ->withRole([Role::ADMIN])
        ->create();

    Sanctum::actingAs($user);

    getJson('/_test/role-protected')
        ->assertOk();
});

test('allows access when the user has any of the allowed roles', function (): void {
    $user = UserModel::factory()
        ->withRole([Role::ADMIN])
        ->create();

    Sanctum::actingAs($user);

    getJson('/_test/role-everyone')
        ->assertOk();

    $user2 = UserModel::factory()
        ->withRole([Role::USER])
        ->create();

    Sanctum::actingAs($user2);

    getJson('/_test/role-everyone')
        ->assertOk();
});

test('allows access when the user has multiple roles including admin', function (): void {
    $user = UserModel::factory()
        ->withRole([Role::USER, Role::ADMIN])
        ->create();

    Sanctum::actingAs($user);

    getJson('/_test/role-everyone')
        ->assertOk();
});

test('throws an exception when the middleware contains an unknown role', function (): void {
    $user = UserModel::factory()
        ->withRole([Role::ADMIN])
        ->create();

    Sanctum::actingAs($user);

    withoutExceptionHandling();

    getJson('/_test/role-unknown');
})->throws(InvalidArgumentException::class, 'Invalid role provided to middleware.');
