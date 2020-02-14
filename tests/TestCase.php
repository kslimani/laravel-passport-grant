<?php

namespace Tests;

use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Sk\Passport\GrantTypesServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected $grantClient;

    protected function getPackageProviders($app)
    {
        return [
            PassportServiceProvider::class,
            GrantTypesServiceProvider::class,
        ];
    }

    protected function setUpPassport()
    {
        // Run Laravel & Passport database migrations
        $this->loadLaravelMigrations();
        $this->artisan('vendor:publish', ['--tag' => 'passport-migrations'])->run();
        $this->loadMigrationsFrom(database_path('migrations'));

        // Create grant client
        $this->grantClient = $this->app
            ->make(ClientRepository::class)
            ->createPasswordGrantClient(null, 'Test', 'http://localhost');

        // Register Passport routes
        Passport::routes();

        // Create Passport keys once
        if (file_exists(storage_path('oauth-private.key'))
            && file_exists(storage_path('oauth-public.key'))) {
            return;
        }

        $this->artisan('passport:keys')->run();
    }

    protected function getEnvironmentSetUp($app)
    {
        // Load default configuration file
        $root = realpath(dirname(__FILE__).'/..');
        $passportgrant = include($root.'/config/passportgrant.php');
        $app->config->set('passportgrant', $passportgrant);

        // Setup test database
        $app->config->set('database.default', 'testbench');
        $app->config->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
