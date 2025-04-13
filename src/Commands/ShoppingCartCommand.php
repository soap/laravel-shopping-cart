<?php

namespace Soap\LaravelShoppingCart\Commands;

use Illuminate\Console\Command;

class ShoppingCartCommand extends Command
{
    public $signature = 'laravel-shopping-cart';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
