<?php

namespace Soap\ShoppingCart;

use Illuminate\Auth\Events\Logout;
use Illuminate\Session\SessionManager;
use Soap\ShoppingCart\Commands\ShoppingCartCommand;
use Soap\ShoppingCart\Contracts\CouponReservationStoreInterface;
use Soap\ShoppingCart\Contracts\CouponServiceInterface;
use Soap\ShoppingCart\Contracts\UserResolverInterface;
use Soap\ShoppingCart\ReservationStores\DatabaseCouponReservationStore;
use Soap\ShoppingCart\Services\CouponService;
use Soap\ShoppingCart\Supports\ConditionContextFactory;
use Soap\ShoppingCart\Supports\UserResolver;
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
            ->hasMigrations([
                'create_shopping_carts_table',
                'create_coupon_reservations_table',
                'create_cart_conditions_table',
            ])
            ->hasCommand(ShoppingCartCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->scoped('shopping-cart', function ($app) {
            return new ShoppingCart($app->make('session'), $app->make('events'));
        });
        $this->app->alias('shopping-cart', ShoppingCart::class); // for backward compatibility

        $this->app->singleton(CouponServiceInterface::class, CouponService::class);

        $this->app->singleton(UserResolverInterface::class, UserResolver::class);

        $this->app->singleton(CouponReservationStoreInterface::class, function ($app) {
            $class = config('shopping-cart.coupon_reservation_store', DatabaseCouponReservationStore::class);

            if (! class_exists($class)) {
                throw new \InvalidArgumentException("The coupon reservation store class [{$class}] does not exist.");
            }

            return new $class;
        });

        $this->app->scoped(CouponManager::class, function ($app) {
            return new CouponManager(
                $app->make('session'),
                $app->make('events'),
                $app->make(UserResolverInterface::class),
                $app->make(CouponServiceInterface::class),
                $app->make(CouponReservationStoreInterface::class),
            );
        });

        $this->app->singleton(ConditionContextFactory::class, function ($app) {
            return new ConditionContextFactory($app->make(UserResolverInterface::class));
        });

        $this->app->scoped(ConditionManager::class, function ($app) {
            $factory = $app->make(ConditionContextFactory::class);

            return new ConditionManager(
                fn ($objectAccess = true) => $factory->buildFromRuntime($objectAccess)
            );
        });
    }

    public function packageBooted(): void
    {
        $this->app['events']->listen(Logout::class, function () {
            if ($this->app['config']->get('shopping-cart.destroy_on_logout')) {
                $this->app->make(SessionManager::class)->forget('cart');
                $this->app->make(SessionManager::class)->forget('coupons');
            }
        });
    }
}
