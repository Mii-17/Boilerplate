<?php

declare(strict_types=1);

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Infrastructure\Models\UserModel;
use Src\Iam\User\Infrastructure\Notifications\VerifyEmailNotification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('shows the registration form', function (): void {
    /** @var view-string $view */
    $view = 'iam.auth.register';

    get(route('register'))
        ->assertOk()
        ->assertViewIs($view)
        ->assertSee('name="email"', false);
});

test('registers a user, logs them in and redirects to the verification notice', function (): void {
    Notification::fake();

    post('/register', [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'Registration successful. Please verify your email.')
        ->assertHeader('X-Correlation-ID');

    $user = UserModel::query()->where('email', 'user@example.com')->sole();

    expect($user->roles()->where('role', Role::USER->value)->exists())->toBeTrue();
    expect(auth()->guest())->toBeFalse();
});

test('redirects back with validation errors', function (array $payload, string $field): void {
    post('/register', $payload)
        ->assertRedirect()
        ->assertSessionHasErrors($field)
        ->assertSessionHasInput('email');
})->with([
    'name is missing' => [
        ['email' => 'user@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'],
        'name',
    ],
    'email is invalid' => [
        ['name' => 'User', 'email' => 'not-an-email', 'password' => 'Password123!', 'password_confirmation' => 'Password123!'],
        'email',
    ],
    'password is not confirmed' => [
        ['name' => 'User', 'email' => 'user@example.com', 'password' => 'Password123!', 'password_confirmation' => 'Other123!'],
        'password',
    ],
    'password is too weak' => [
        ['name' => 'User', 'email' => 'user@example.com', 'password' => 'secret', 'password_confirmation' => 'secret'],
        'password',
    ],
]);

test('redirects back when the email is already taken', function (): void {
    UserModel::factory()->create(['email' => 'user@example.com']);

    post('/register', [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])
        ->assertSessionHas('error', 'The user with email <user@example.com> already exists.');
    expect(auth()->guest())->toBeTrue();
});

test('sends the verification notification on demand', function (): void {
    Notification::fake();

    post('/register', [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect(route('verification.notice'));

    Notification::assertSentOnDemand(
        VerifyEmailNotification::class,
        fn (VerifyEmailNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $channels === ['mail']
            && $notifiable->routes['mail'] === 'user@example.com',
    );
});

test('redirects authenticated users away from the registration form', function (): void {
    actingAs(UserModel::factory()->create());

    get(route('register'))->assertRedirect();
});
