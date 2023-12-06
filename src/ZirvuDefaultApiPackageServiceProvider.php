<?php

namespace Zirvu\Api;

use Illuminate\Support\ServiceProvider;

class ZirvuApiPackageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/zirvu/api/classes.php' => config_path('zirvu/api/classes.php'),
        ], 'config');
    }

    public function register()
    {
        // ...
    }
}
