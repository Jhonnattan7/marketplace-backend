<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Evita choques de llaves foráneas estrictas de SQLite en memoria
        if (config('database.default') === 'sqlite' || config('database.connections.sqlite.database') === ':memory:') {
            Schema::disableForeignKeyConstraints();
        }
    }
}
