<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

use function Pest\Laravel\get;

it('reports healthy when database and redis respond', function (): void {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getPdo')->once()->andReturn(Mockery::mock(PDO::class));

    DB::shouldReceive('connection')->once()->andReturn($connection);
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    get('/up')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'checks' => [
                'database' => true,
                'redis' => true,
            ],
        ]);
});

it('returns 503 when database is down', function (): void {
    DB::shouldReceive('connection')->once()->andThrow(new RuntimeException('db down'));
    Redis::shouldReceive('ping')->once()->andReturn('PONG');

    get('/up')
        ->assertStatus(503)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('checks.database', false)
        ->assertJsonPath('checks.redis', true);
});

it('returns 503 when redis is down', function (): void {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getPdo')->once()->andReturn(Mockery::mock(PDO::class));

    DB::shouldReceive('connection')->once()->andReturn($connection);
    Redis::shouldReceive('ping')->once()->andThrow(new RuntimeException('redis down'));

    get('/up')
        ->assertStatus(503)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('checks.database', true)
        ->assertJsonPath('checks.redis', false);
});
