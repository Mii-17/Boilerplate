<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\WelcomeNotification;
use Tests\Support\SignedVerificationUrl;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

test('shows the verification notice', function (): void {
    actingAs(UserModel::factory()->unverified()->create());

    /** @var view-string $view */
    $view = 'iam.auth.verify';

    get(route('verification.notice'))
        ->assertOk()
        ->assertViewIs($view);
});

test('redirects guests to login after verifying the email', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create(['email' => 'user@example.com']);
    $token = $user->verification_token;
    assert(is_string($token));

    get(SignedVerificationUrl::forUser($user, $token))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Email verified successfully.');

    expect(UserModel::query()->whereKey($user->id)->value('verified_at'))->not->toBeNull();

    Notification::assertSentTo($user, WelcomeNotification::class);
});

test('redirects authenticated users to the home page after verifying', function (): void {
    $user = UserModel::factory()->unverified()->create();
    $token = $user->verification_token;
    assert(is_string($token));

    actingAs($user);

    get(SignedVerificationUrl::forUser($user, $token))
        ->assertRedirect('/')
        ->assertSessionHas('status', 'Email verified successfully.');
});

test('still returns JSON for api clients', function (): void {
    $user = UserModel::factory()->unverified()->create(['email' => 'user@example.com']);
    $token = $user->verification_token;
    assert(is_string($token));

    getJson(SignedVerificationUrl::forUser($user, $token))
        ->assertOk()
        ->assertJson([
            'id' => $user->id,
            'email' => 'user@example.com',
            'isVerified' => true,
        ]);
});

test('rejects an invalid signature', function (): void {
    $user = UserModel::factory()->unverified()->create();

    get(route('iam.verification.verify', [
        'userId' => $user->id,
        'token' => 'irrelevant',
    ]))->assertForbidden();
});

test('rejects an expired signature', function (): void {
    $user = UserModel::factory()->unverified()->create();

    get(SignedVerificationUrl::forUser($user, 'irrelevant', now()->subMinute()))
        ->assertForbidden();
});

test('redirects back with an error for an invalid token', function (): void {
    $user = UserModel::factory()->unverified()->create();

    get(SignedVerificationUrl::forUser($user, 'not-the-real-token'))
        ->assertSessionHas('error', 'Invalid verification token.');
});

test('redirects back with an error for an expired token', function (): void {
    $user = UserModel::factory()->unverified()->create([
        'verification_expires_at' => now()->subMinute(),
    ]);
    $token = $user->verification_token;
    assert(is_string($token));

    get(SignedVerificationUrl::forUser($user, $token))
        ->assertSessionHas('error', 'Verification token has expired.');
});

test('redirects back with an error when the user does not exist', function (): void {
    $userId = Str::uuid()->toString();

    get(SignedVerificationUrl::for($userId, 'irrelevant'))
        ->assertSessionHas('error', "User with id {$userId} not found.");
});

test('redirects back with an error when the email is already verified', function (): void {
    $user = UserModel::factory()->create();

    get(SignedVerificationUrl::forUser($user, 'irrelevant'))
        ->assertSessionHas('error', "The user with id <{$user->id}> is already verified.");
});
