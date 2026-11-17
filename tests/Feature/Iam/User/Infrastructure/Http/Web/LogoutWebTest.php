<?php

declare(strict_types=1);

use Src\Iam\User\Infrastructure\Models\UserModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

test('logs out and redirects to the home page', function (): void {
    $user = UserModel::factory()->create();
    actingAs($user);

    post(route('logout'))
        ->assertRedirect('/')
        ->assertSessionHas('status', 'You have been logged out.');
    expect(auth()->guest())->toBeTrue();
});

test('invalidates the session on logout', function (): void {
    actingAs(UserModel::factory()->create());
    $sessionId = session()->getId();

    post(route('logout'))->assertRedirect('/');

    expect(session()->getId())->not->toBe($sessionId);
});

test('requires authentication', function (): void {
    post(route('logout'))->assertRedirect(route('login'));
});
