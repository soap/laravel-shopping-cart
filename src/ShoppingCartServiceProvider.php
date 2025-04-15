<?php

namespace Soap\ShoppingCart;

use Illuminate\Auth\Events\Logout;
use Illuminate\Session\SessionManager;
use Soap\ShoppingCart\Commands\ShoppingCartCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ShoppingCartServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-shopping-cart')
            ->hasConfigFile()
            ->hasMigration('create_shopping_carts_table')
            ->hasCommand(ShoppingCartCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('shopping-cart', ShoppingCart::class);
    }

    public function PackageBooted(): void
    {
        $this->app['events']->listen(Logout::class, function () {
            if ($this->app['config']->get('shopping-cart.destroy_on_logout')) {
                $this->app->make(SessionManager::class)->forget('cart');
            }
        });
    }
}
