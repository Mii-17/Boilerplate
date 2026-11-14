<?php

declare(strict_types=1);

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\VerifyEmailNotification;

use function Pest\Laravel\patchJson;

test('changes the email of an unverified user', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create(['email' => 'old@example.com']);
    $oldToken = $user->verification_token;

    Sanctum::actingAs($user);

    patchJson('/api/user/email', ['email' => 'new@example.com'])
        ->assertOk()
        ->assertJson([
            'id' => $user->id,
            'email' => 'new@example.com',
        ]);

    $user->refresh();

    expect($user->email)->toBe('new@example.com');
    expect($user->verification_token)->not->toBe($oldToken);

    Notification::assertSentOnDemand(
        VerifyEmailNotification::class,
        fn (VerifyEmailNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $channels === ['mail']
            && $notifiable->routes['mail'] === 'new@example.com',
    );
});

test('rejects an unauthenticated email change', function (): void {
    patchJson('/api/user/email', ['email' => 'new@example.com'])
        ->assertUnauthorized();
});

test('rejects changing the email of a verified user', function (): void {
    $user = UserModel::factory()->create(['email' => 'old@example.com']);

    Sanctum::actingAs($user);

    patchJson('/api/user/email', ['email' => 'new@example.com'])
        ->assertConflict()
        ->assertJsonStructure(['message']);
});

test('rejects changing to an email that is already taken', function (): void {
    UserModel::factory()->create(['email' => 'taken@example.com']);
    $user = UserModel::factory()->unverified()->create(['email' => 'old@example.com']);

    Sanctum::actingAs($user);

    patchJson('/api/user/email', ['email' => 'taken@example.com'])
        ->assertConflict()
        ->assertJsonStructure(['message']);
});

test('rejects invalid email change payloads', function (array $payload, string $field): void {
    $user = UserModel::factory()->unverified()->create();

    Sanctum::actingAs($user);

    patchJson('/api/user/email', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'email is missing' => [[], 'email'],
    'email is invalid' => [['email' => 'not-an-email'], 'email'],
]);
