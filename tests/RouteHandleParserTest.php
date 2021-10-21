<?php

use Ensi\LaravelOpenApiServerGenerator\DTO\ParsedRouteHandler;
use Ensi\LaravelOpenApiServerGenerator\Utils\RouteHandlerParser;

it('can parse handler without action', function () {
    $result = (new RouteHandlerParser())->parse("App\\Http\\Controllers\\CreateUser");
    expect($result)->toEqual(new ParsedRouteHandler(
        namespace: "App\Http\Controllers",
        class: "CreateUser",
        fqcn: "App\Http\Controllers\CreateUser",
        method: null,
    ));
});


it('can parse handler with leading slash', function () {
    $result = (new RouteHandlerParser())->parse("\\App\\Http\\Controllers\\CreateUser");
    expect($result)->toEqual(new ParsedRouteHandler(
        namespace: "App\Http\Controllers",
        class: "CreateUser",
        fqcn: "App\Http\Controllers\CreateUser",
        method: null,
    ));
});

it('can parse handler with action', function (string $handler) {
    $result = (new RouteHandlerParser())->parse($handler);
    expect($result)->toEqual(new ParsedRouteHandler(
        namespace: "App\Http\Controllers",
        class: "UsersController",
        fqcn: "App\Http\Controllers\UsersController",
        method: "store",
    ));
})->with([
    "App\\Http\\Controllers\\UsersController@store",
    "App\\Http\\Controllers\\UsersController::store",
    "\\App\\Http\\Controllers\\UsersController@store",
    "\\App\\Http\\Controllers\\UsersController::store",
]);

it('can parse handler without namespace', function () {
    $result = (new RouteHandlerParser())->parse("CreateUser@foo");
    expect($result)->toEqual(new ParsedRouteHandler(
        namespace: null,
        class: "CreateUser",
        fqcn: "CreateUser",
        method: "foo",
    ));
});
