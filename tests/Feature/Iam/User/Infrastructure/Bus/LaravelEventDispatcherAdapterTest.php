<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Src\Iam\User\Application\Ports\EventDispatcherInterface;
use Src\Iam\User\Domain\Enums\Role;
use Src\Iam\User\Domain\Events\UserRegistered;
use Src\Iam\User\Domain\Events\UserVerified;
use Src\Iam\User\Domain\ValueObjects\RolesCollection;
use Src\Iam\User\Domain\ValueObjects\UserId;
use Src\Iam\User\Infrastructure\Bus\LaravelEventDispatcherAdapter;
use Src\Shared\Domain\ValueObjects\Email;

function laravelEventDispatcherAdapter(): LaravelEventDispatcherAdapter
{
    return app(LaravelEventDispatcherAdapter::class);
}

test('dispatches all events to their listeners in the correct order', function (): void {
    $laravelEventDispatcherAdapter = laravelEventDispatcherAdapter();

    $receivedEvents = [];

    Event::listen(UserRegistered::class, function ($event) use (&$receivedEvents): void {
        $receivedEvents[] = $event;
    });

    Event::listen(UserVerified::class, function ($event) use (&$receivedEvents): void {
        $receivedEvents[] = $event;
    });

    $userId = UserId::generate();
    $email = Email::fromString('user@example.com');
    $roles = new RolesCollection([Role::USER]);
    $eventA = new UserRegistered($userId, 'User Example', $email, $roles);
    $eventB = new UserVerified($userId, $email);

    $laravelEventDispatcherAdapter->dispatchAll([$eventA, $eventB]);

    expect($receivedEvents)->toBe([$eventA, $eventB]);
});

test('does not dispatch any events when the events array is empty', function (): void {
    $laravelEventDispatcherAdapter = laravelEventDispatcherAdapter();

    Event::fake();

    $laravelEventDispatcherAdapter->dispatchAll([]);

    Event::assertNothingDispatched();
});

test('resolves EventDispatcherInterface to LaravelEventDispatcherAdapter from container', function (): void {
    expect(app(EventDispatcherInterface::class))->toBeInstanceOf(LaravelEventDispatcherAdapter::class);
});
