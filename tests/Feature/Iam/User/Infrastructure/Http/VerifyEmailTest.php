<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\WelcomeNotification;
use Tests\Support\SignedVerificationUrl;

use function Pest\Laravel\getJson;

test('verifies the email with a valid signed link', function (): void {
    Notification::fake();

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

    expect(UserModel::query()->whereKey($user->id)->value('verified_at'))->not->toBeNull();

    Notification::assertSentTo($user, WelcomeNotification::class);
});

test('rejects a verification link without a valid signature', function (): void {
    $user = UserModel::factory()->unverified()->create();
    $token = $user->verification_token;
    assert(is_string($token));

    getJson(route('iam.verification.verify', ['userId' => $user->id, 'token' => $token]))
        ->assertForbidden();
});

test('rejects a verification link with an expired signature', function (): void {
    $user = UserModel::factory()->unverified()->create();
    $token = $user->verification_token;
    assert(is_string($token));

    getJson(SignedVerificationUrl::forUser($user, $token, now()->subMinute()))
        ->assertForbidden();
});

test('rejects an invalid verification token', function (): void {
    $user = UserModel::factory()->unverified()->create();

    getJson(SignedVerificationUrl::forUser($user, 'not-the-real-token'))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Invalid verification token.']);
});

test('rejects an expired verification token', function (): void {
    $user = UserModel::factory()->unverified()->create([
        'verification_expires_at' => now()->subMinute(),
    ]);
    $token = $user->verification_token;
    assert(is_string($token));

    getJson(SignedVerificationUrl::forUser($user, $token))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Verification token has expired.']);
});

test('returns 404 when the user does not exist', function (): void {
    getJson(SignedVerificationUrl::for(Str::uuid()->toString(), 'irrelevant'))
        ->assertNotFound();
});

test('rejects verification when the email is already verified', function (): void {
    $user = UserModel::factory()->create();

    getJson(SignedVerificationUrl::forUser($user, 'irrelevant'))->assertConflict();
});
