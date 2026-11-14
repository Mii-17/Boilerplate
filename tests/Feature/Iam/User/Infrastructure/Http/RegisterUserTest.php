<?php

declare(strict_types=1);

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\VerifyEmailNotification;

use function Pest\Laravel\postJson;

test('registers a new user', function (): void {
    Notification::fake();

    $response = postJson('/api/register', [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'name', 'email'])
        ->assertJson([
            'name' => 'User',
            'email' => 'user@example.com',
        ]);

    expect(UserModel::query()->where('email', 'user@example.com')->exists())->toBeTrue();

    Notification::assertSentOnDemand(
        VerifyEmailNotification::class,
        fn (VerifyEmailNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $channels === ['mail']
            && $notifiable->routes['mail'] === 'user@example.com',
    );
});

test('rejects invalid registration payloads', function (array $payload, string $field): void {
    postJson('/api/register', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'name is missing' => [
        ['email' => 'user@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'],
        'name',
    ],
    'email is invalid' => [
        ['name' => 'User
        ', 'email' => 'not-an-email', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'],
        'email',
    ],
    'password is not confirmed' => [
        ['name' => 'User
        ', 'email' => 'user@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Other123!'],
        'password',
    ],
    'password is too weak' => [
        ['name' => 'User
        ', 'email' => 'user@example.com', 'password' => 'secret', 'password_confirmation' => 'secret'],
        'password',
    ],
]);

test('rejects a registration with an already used email', function (): void {
    UserModel::factory()->create(['email' => 'user@example.com']);

    postJson('/api/register', [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])
        ->assertConflict()
        ->assertJsonStructure(['message']);
});

test('assigns the user role to the new account', function (): void {
    Notification::fake();

    postJson('/api/register', [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertCreated();

    $user = UserModel::query()->where('email', 'user@example.com')->sole();

    expect($user->roles()->where('role', Role::USER->value)->exists())->toBeTrue();
});
test('replays the response for a repeated idempotency key', function (): void {
    Notification::fake();

    $key = Str::uuid()->toString();
    $payload = [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ];

    $first = postJson('/api/register', $payload, ['Idempotency-Key' => $key])
        ->assertCreated();

    $second = postJson('/api/register', $payload, ['Idempotency-Key' => $key])
        ->assertCreated();

    expect($second->json())->toBe($first->json());
    expect($second->headers->get('Idempotency-Key'))->toBe($key);
    expect(UserModel::query()->where('email', 'user@example.com')->count())->toBe(1);
});

test('rejects reusing an idempotency key with a different payload', function (): void {
    Notification::fake();

    $key = Str::uuid()->toString();

    postJson('/api/register', [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ], ['Idempotency-Key' => $key])->assertCreated();

    postJson('/api/register', [
        'name' => 'User',
        'email' => 'other@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ], ['Idempotency-Key' => $key])
        ->assertConflict()
        ->assertJson(['message' => 'The idempotency key was already used with a different request.']);
});
