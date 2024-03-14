<?php

namespace ITUTUMedia\LaravelModulesLivewireForm\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ITUTUMedia\LaravelModulesLivewireForm\LaravelModulesLivewireForm
 */
class LaravelModulesLivewireForm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \ITUTUMedia\LaravelModulesLivewireForm\LaravelModulesLivewireForm::class;
    }
}
