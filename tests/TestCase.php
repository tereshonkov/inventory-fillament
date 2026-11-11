<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to run against anything but the in-memory SQLite database.
     *
     * Tests use RefreshDatabase (migrate:fresh). If `bootstrap/cache/config.php` exists, Laravel ignores the
     * `phpunit.xml` environment and the tests would wipe the database from `.env` (e.g. the local MySQL).
     * This guard runs before the traits migrate anything.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $default = config('database.default');
        $database = config("database.connections.{$default}.database");

        if ($default !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Tests must run on in-memory SQLite, but the app is configured with [{$default}] / [{$database}]. "
                .'The configuration is probably cached: run `php artisan config:clear`.'
            );
        }
    }
}
