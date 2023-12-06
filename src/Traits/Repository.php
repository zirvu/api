<?php

namespace Zirvu\Api\Traits;

use Zirvu\Api\Traits\RepositoryExtension\CommonRepository;
use Zirvu\Api\Traits\RepositoryExtension\Eloquent;

trait Repository
{
    use CommonRepository, Eloquent;
}