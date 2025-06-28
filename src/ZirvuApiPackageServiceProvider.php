<?php

namespace Zirvu\Api;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ZirvuApiPackageServiceProvider extends ServiceProvider
{
    protected $_dFunc = true;
    
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
