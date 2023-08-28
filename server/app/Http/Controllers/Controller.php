<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use App\Traits\ApiResponser;
use App\Traits\PermissionHandler;

class Controller extends BaseController
{
    use ApiResponser;

    use PermissionHandler;
}
