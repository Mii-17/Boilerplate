<?php

declare(strict_types=1);

use Src\Iam\User\Infrastructure\Models\UserModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

test('shows the login form', function (): void {
    /** @var view-string $view */
    $view = 'iam.auth.login';

    get(route('login'))
        ->assertOk()
        ->assertViewIs($view)
        ->assertSee('name="password"', false);
});

test('authenticates the session and redirects to the intended url', function (): void {
    $user = UserModel::factory()->create(['email' => 'user@example.com']);

    withSession(['url.intended' => '/email/change']);

    post('/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ])
        ->assertRedirect('/email/change')
        ->assertSessionHas('status', 'Welcome back.');
    expect(auth()->user()?->is($user))->toBeTrue();
});

test('redirects to the home page when there is no intended url', function (): void {
    UserModel::factory()->create(['email' => 'user@example.com']);

    post('/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ])
        ->assertRedirect('/')
        ->assertSessionHas('status', 'Welcome back.');
});

test('regenerates the session id on login', function (): void {
    UserModel::factory()->create(['email' => 'user@example.com']);
    $sessionId = session()->getId();

    post('/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ])->assertRedirect('/');

    expect(session()->getId())->not->toBe($sessionId);
});

test('redirects back with an error for invalid credentials', function (array $payload): void {
    UserModel::factory()->create(['email' => 'user@example.com']);

    post('/login', $payload)
        ->assertSessionHas('error', 'The credentials provided are invalid.');
    expect(auth()->guest())->toBeTrue();
})->with([
    'wrong password' => [['email' => 'user@example.com', 'password' => 'Wrong123!']],
    'unknown email' => [['email' => 'nobody@example.com', 'password' => 'Password123!']],
]);

test('allows unverified users to log in and redirects to the verification notice', function (): void {
    $user = UserModel::factory()->unverified()->create(['email' => 'user@example.com']);

    post('/login', [
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'Welcome back.');

    expect(auth()->user()?->is($user))->toBeTrue();
});

test('redirects back with validation errors', function (array $payload, string $field): void {
    post('/login', $payload)
        ->assertRedirect()
        ->assertSessionHasErrors($field);
})->with([
    'email is missing' => [['password' => 'Password123!'], 'email'],
    'email is invalid' => [['email' => 'not-an-email', 'password' => 'Password123!'], 'email'],
    'password is missing' => [['email' => 'user@example.com'], 'password'],
]);

test('throttles repeated login attempts', function (): void {
    UserModel::factory()->create(['email' => 'user@example.com']);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        post('/login', [
            'email' => 'user@example.com',
            'password' => 'Wrong123!',
        ])->assertSessionHas('error');
    }

    post('/login', [
        'email' => 'user@example.com',
        'password' => 'Wrong123!',
    ])->assertStatus(429);
});

test('redirects authenticated users away from the login form', function (): void {
    actingAs(UserModel::factory()->create());

    get(route('login'))->assertRedirect();
});
