<?php

namespace ClarkeWing\Handoff\Tests;

use ClarkeWing\Handoff\HandoffServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    protected function getPackageProviders($app): array
    {
        return [
            HandoffServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        Model::preventLazyLoading();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUpDatabase($app): void
    {
        $schema = $app['db']->connection()->getSchemaBuilder();
    }
}
