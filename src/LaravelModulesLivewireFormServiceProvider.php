<?php

namespace ITUTUMedia\LaravelModulesLivewireForm;

use ITUTUMedia\LaravelModulesLivewireForm\Commands\LaravelModulesLivewireFormCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelModulesLivewireFormServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-modules-livewire-form')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-modules-livewire-form_table')
            ->hasCommand(LaravelModulesLivewireFormCommand::class);
    }
}
