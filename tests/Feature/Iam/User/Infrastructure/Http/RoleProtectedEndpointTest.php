<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Infrastructure\Models\UserModel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    Route::middleware(['api', 'auth:sanctum', 'role:admin'])
        ->get('/_test/admin-only', fn () => response()->json(['ok' => true]));
});

function bearerTokenFor(UserModel $user): string
{
    $token = postJson('/api/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ])->assertOk()->json('token');

    assert(is_string($token));

    return $token;
}

test('allows an admin to access a role protected endpoint', function (): void {
    $admin = UserModel::factory()->withRole([Role::ADMIN])->create();

    getJson('/_test/admin-only', ['Authorization' => 'Bearer '.bearerTokenFor($admin)])
        ->assertOk()
        ->assertJson(['ok' => true]);
});

test('forbids a regular user from accessing a role protected endpoint', function (): void {
    $user = UserModel::factory()->create();

    getJson('/_test/admin-only', ['Authorization' => 'Bearer '.bearerTokenFor($user)])
        ->assertForbidden();
});

test('requires authentication to access a role protected endpoint', function (): void {
    getJson('/_test/admin-only')->assertUnauthorized();
});

test('rejects an invalid bearer token', function (): void {
    getJson('/_test/admin-only', ['Authorization' => 'Bearer not-a-real-token'])
        ->assertUnauthorized();
});
