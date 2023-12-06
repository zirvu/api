<?php

namespace Zirvu\Api\Traits\ServiceExtension;

use Zirvu\Api\Utils\UtilsService;

trait LoaderService
{

	protected $utilsService;

	public function loadUtilsService() { $this->utilsService = app(UtilsService::class); }


    public function __call($name, $arguments)
    {
        $classes = config("zirvu.api.classes") ?? [];
        $sub = "";

        if (strpos($name, 'load') === 0 && substr($name, -7) === 'Service') {
            $className = substr($name, 4, -7);

            foreach ($classes as $category => $classNames) {
                foreach ($classNames as $key => $value) {
                    if ( $value == $className ) {
                        if ( $category != "root" ) {
                            $sub = $category."\\";
                        }
                    }
                }
            }

            $serviceContractPath = "App\Services\Contract\\{$sub}{$className}Contract";

            $this->addService($className, $serviceContractPath);
        } else {
            throw new \BadMethodCallException("Method {$name} not found.");
        }
    }

    protected function addService($className, $serviceContractPath)
    {
        $convert = strtolower(substr($className, 0, 1)) . substr($className, 1);

        $this->{$convert.'Service'} = app($serviceContractPath);
    }

}