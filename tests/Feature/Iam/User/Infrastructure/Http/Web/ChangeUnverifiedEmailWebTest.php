<?php

declare(strict_types=1);

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\VerifyEmailNotification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

test('shows the email change form', function (): void {
    actingAs(UserModel::factory()->unverified()->create());

    /** @var view-string $view */
    $view = 'iam.auth.email';

    get(route('email.change'))
        ->assertOk()
        ->assertViewIs($view)
        ->assertSee('name="email"', false);
});

test('changes the email and redirects to the verification notice', function (): void {
    Notification::fake();

    $user = UserModel::factory()->unverified()->create(['email' => 'old@example.com']);
    $oldToken = $user->verification_token;
    actingAs($user);

    patch(route('email.update'), ['email' => 'new@example.com'])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'Email updated. Please verify your new address.');

    $user->refresh();

    expect($user->email)->toBe('new@example.com');
    expect($user->verification_token)->not->toBe($oldToken);

    Notification::assertSentOnDemand(
        VerifyEmailNotification::class,
        fn (VerifyEmailNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $channels === ['mail']
            && $notifiable->routes['mail'] === 'new@example.com',
    );
});

test('changes the email using the session created at registration', function (): void {
    Notification::fake();

    post('/register', [
        'name' => 'User',
        'email' => 'old@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect(route('verification.notice'));

    patch(route('email.update'), ['email' => 'new@example.com'])
        ->assertRedirect(route('verification.notice'));

    expect(UserModel::query()->where('email', 'new@example.com')->exists())->toBeTrue();
});

test('redirects guests to the login page', function (): void {
    get(route('email.change'))->assertRedirect(route('login'));
    patch(route('email.update'), ['email' => 'new@example.com'])->assertRedirect(route('login'));
});

test('redirects back when the new email is already taken', function (): void {
    UserModel::factory()->create(['email' => 'taken@example.com']);
    actingAs(UserModel::factory()->unverified()->create(['email' => 'old@example.com']));

    patch(route('email.update'), ['email' => 'taken@example.com'])
        ->assertSessionHas('error', 'User email <taken@example.com> is already taken.');
});

test('redirects back when the user is already verified', function (): void {
    $user = UserModel::factory()->create(['email' => 'old@example.com']);
    actingAs($user);

    patch(route('email.update'), ['email' => 'new@example.com'])
        ->assertSessionHas('error', "The user with id <{$user->id}> is already verified.");
});

test('redirects back with validation errors', function (array $payload, string $field): void {
    actingAs(UserModel::factory()->unverified()->create());

    patch(route('email.update'), $payload)
        ->assertRedirect()
        ->assertSessionHasErrors($field);
})->with([
    'email is missing' => [[], 'email'],
    'email is invalid' => [['email' => 'not-an-email'], 'email'],
]);
