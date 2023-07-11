<?php

namespace Ensi\LaravelOpenApiServerGenerator\Tests\expects\Policies;

use Illuminate\Auth\Access\Response;

class LaravelWithoutTraitPolicy
{
    public function search(): Response
    {
        return Response::allow();
    }
}
