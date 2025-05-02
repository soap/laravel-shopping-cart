<?php

namespace Soap\ShoppingCart\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserResolverInterface
{
    /**
     * Get the user object from the request.
     */
    public function resolve(int|string|null $userId = null, ?string $guard = null): ?Authenticatable;
}
