<?php

namespace ITUTUMedia\LaravelModulesLivewireForm\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use ITUTUMedia\LaravelModulesLivewireForm\LaravelModulesLivewireFormServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'ITUTUMedia\\LaravelModulesLivewireForm\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelModulesLivewireFormServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravel-modules-livewire-form_table.php.stub';
        $migration->up();
        */
    }
}
