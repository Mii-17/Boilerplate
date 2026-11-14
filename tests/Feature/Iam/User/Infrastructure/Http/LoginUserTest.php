<?php

declare(strict_types=1);

use Src\Iam\User\Infrastructure\Models\UserModel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

test('returns a bearer token for valid credentials', function (): void {
    UserModel::factory()->create(['email' => 'user@example.com']);

    $response = postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'token_type'])
        ->assertJson(['token_type' => 'Bearer']);

    expect($response->json('token'))->toBeString();
});

test('authenticates protected endpoints with the issued token', function (): void {
    $user = UserModel::factory()->create(['email' => 'user@example.com']);

    $token = postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ])->assertOk()->json('token');

    assert(is_string($token));

    getJson('/api/user', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email);
});

test('rejects invalid credentials', function (array $payload): void {
    UserModel::factory()->create(['email' => 'user@example.com']);

    postJson('/api/login', $payload)
        ->assertUnauthorized()
        ->assertJson(['message' => 'The credentials provided are invalid.']);
})->with([
    'wrong password' => [['email' => 'user@example.com', 'password' => 'Wrong123!']],
    'unknown email' => [['email' => 'nobody@example.com', 'password' => 'Password123!']],
]);

test('rejects login when the email is not verified', function (): void {
    UserModel::factory()->unverified()->create(['email' => 'user@example.com']);

    postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ])
        ->assertForbidden()
        ->assertJsonStructure(['message']);
});

test('rejects invalid login payloads', function (array $payload, string $field): void {
    postJson('/api/login', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'email is missing' => [['password' => 'Password123!'], 'email'],
    'email is invalid' => [['email' => 'not-an-email', 'password' => 'Password123!'], 'email'],
    'password is missing' => [['email' => 'user@example.com'], 'password'],
    'password is too long' => [['email' => 'user@example.com', 'password' => str_repeat('a', 256)], 'password'],
]);

test('throttles repeated login attempts', function (): void {
    UserModel::factory()->create(['email' => 'user@example.com']);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'Wrong123!',
        ])->assertUnauthorized();
    }

    $response = postJson('/api/login', [
        'email' => 'user@example.com',
        'password' => 'Wrong123!',
    ]);

    $response->assertTooManyRequests();
    expect($response->headers->has('Retry-After'))->toBeTrue();
});

test('requires authentication to show the current user', function (): void {
    getJson('/api/user')->assertUnauthorized();
});
