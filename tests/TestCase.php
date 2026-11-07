<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached()) {
            throw new \RuntimeException(
                'The configuration is cached. Run [php artisan optimize:clear] before running the test suite.'
            );
        }

        $connection = $app['config']->get('database.default');

        if (! is_string($connection)) {
            throw new \RuntimeException('Database connection is not configured.');
        }

        $database = $app['config']->get("database.connections.{$connection}.database");

        if (! is_string($database)) {
            throw new \RuntimeException('Database name is not configured.');
        }

        if (
            $app->environment('testing')
            && $database !== ':memory:'
            && ! str_ends_with($database, '_test')
        ) {
            throw new \RuntimeException(
                "Refusing to run tests against [{$database}]. Use a database ending in _test or :memory:."
            );
        }

        return $app;
    }
}
