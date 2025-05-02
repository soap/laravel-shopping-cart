<?php

namespace Soap\ShoppingCart\Supports;

use Illuminate\Contracts\Auth\Authenticatable;
use Soap\ShoppingCart\Contracts\UserResolverInterface;

class UserResolver implements UserResolverInterface
{
    public function resolve(int|string|null $userId = null, ?string $guard = null): ?Authenticatable
    {
        if ($userId) {
            if ($guard) {
                $provider = config("auth.guards.{$guard}.provider");

                if (! $provider) {
                    return null;
                }

                $modelClass = config("auth.providers.{$provider}.model");

                if (! class_exists($modelClass)) {
                    return null;
                }

                return $modelClass::find($userId);
            }

            foreach (config('auth.guards') as $guardName => $guardConfig) {
                $provider = $guardConfig['provider'] ?? null;

                if (! $provider) {
                    continue;
                }

                $modelClass = config("auth.providers.{$provider}.model");

                if (class_exists($modelClass)) {
                    $user = $modelClass::find($userId);
                    if ($user) {
                        return $user;
                    }
                }
            }

            return null;
        }

        if ($guard) {
            return auth($guard)->user();
        }

        foreach (array_keys(config('auth.guards')) as $guardName) {
            if (auth($guardName)->check()) {
                return auth($guardName)->user();
            }
        }

        return null;
    }
}
