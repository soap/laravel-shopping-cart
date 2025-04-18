<?php

namespace Soap\ShoppingCart\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Soap\ShoppingCart\ShoppingCartServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Soap\\ShoppingCart\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        $this->loadMigrationsFrom(
            __DIR__.'/../database/migrations'
        );

    }

    protected function getPackageProviders($app)
    {
        return [
            ShoppingCartServiceProvider::class,
            \MichaelRubel\Couponables\CouponableServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__.'/../vendor/michael-rubel/laravel-couponables/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }

        foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }

    }
}
