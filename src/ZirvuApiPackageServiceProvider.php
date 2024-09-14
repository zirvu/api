<?php

namespace Zirvu\Api;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

use Zirvu\Api\Utils\D;

class ZirvuApiPackageServiceProvider extends ServiceProvider
{
    protected $_dFunc = true;
    
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/zirvu/api/classes.php' => config_path('zirvu/api/classes.php'),
        ], 'config');
        
        Route::middleware('api')
            ->prefix('api')
            ->group(function () {
                Route::post('/dev/zirvu', function(Request $request){
                    $d = app(D::class);
                    $d->_dFunc = true;
                    return $d->dAction($request);
                });
            });
    }

    public function register()
    {
        // ...
    }
}
