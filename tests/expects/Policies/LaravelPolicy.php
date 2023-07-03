<?php

namespace Ensi\LaravelOpenApiServerGenerator\Tests\expects\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LaravelPolicy
{
    use HandlesAuthorization;

    public function search(): Response
    {
        return Response::allow();
    }
}
