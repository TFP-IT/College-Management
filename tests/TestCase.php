<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Laravel keeps the first authenticated user for the rest of a test, which would make a second
     * token request run as the first user. Real requests never share state, so reset it for token calls.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if (isset($server['HTTP_AUTHORIZATION'])) {
            $this->app['auth']->forgetGuards();
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Safety: tests must never write to the live database.
        if (config('database.connections.mysql.database') === 'mcc_db_live') {
            throw new RuntimeException('Refusing to run tests against mcc_db_live. Set DB_DATABASE in phpunit.xml to a test database.');
        }
    }
}
