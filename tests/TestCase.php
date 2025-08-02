<?php

namespace ClarkeWing\Handoff\Tests;

use ClarkeWing\Handoff\HandoffServiceProvider;
use ClarkeWing\Handoff\Tests\Fixtures\User;
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

        $app['config']->set('app.key', 'base64:XKIF+krFyL/DetvgSFCnmAOUn99navUB2AeeMbDIbcM=');

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('handoff.auth.model', User::class);
    }

    protected function setUpDatabase($app): void
    {
        $schema = $app['db']->connection()->getSchemaBuilder();

        $schema->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        $this->testUser = User::create(['email' => 'test@user.com']);
    }
}
