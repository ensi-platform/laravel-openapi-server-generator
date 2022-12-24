# Laravel OpenApi Server Generator

Generates Laravel application code from Open Api Specification files

## Installation

You can install the package via composer:

`composer require ensi/laravel-openapi-server-generator`

Next you need to publish config file like this:

`php artisan vendor:publish --provider="Ensi\LaravelOpenApiServerGenerator\LaravelOpenApiServerGeneratorServiceProvider"`

and configure all the options.

### Migrating from version 0.x.x

Delete `config/openapi-server-generator.php`, republish it using command above and recreate desired configuration.

#### Basic Usage

Run `php artisan openapi:generate-server`. It will generate all the configured entities from you OAS3 files.  
Override `default_entities_to_generate` configiration with `php artisan openapi:generate-server -e routes,enums`  
Make output more versbose: `php artisan openapi:generate-server -v`  

## Overwriting templates

You can also adjust file templates according to your needs. 
1. Find the needed template inside `templates` directory in this repo;
2. Copy it to to `resources/openapi-server-generator/templates` directory inside your application or configure package to use another directory via `extra_templates_path` option;
3. Change whatever you need.

## Existing entities and generators

### 'routes' => RoutesGenerator::class

Generates laravel route file (`route.php`) for each endpoint in `oas3->paths`  
The following [extension properties](https://github.com/OAI/OpenAPI-Specification/blob/3.0.2/versions/3.0.2.md#specificationExtensions) are used by this generator:

```
x-lg-handler: '\App\Http\Controllers\CustomersController@create' // Optional. Path is ignored if this field is empty. You can use :: instead of @ if you want
x-lg-route-name: 'createCustomer' // Optional. Translates to `->name('createCustomer')`
x-lg-middleware: '\App\Http\Middleware\Authenticate::class,web'  // Optional. Translates to `->middleware([\App\Http\Middleware\Authenticate::class, 'web'])`
x-lg-without-middleware: '\App\Http\Middleware\Authenticate::class,web'  // Optional. Translates to `->withoutMiddleware([\App\Http\Middleware\Authenticate::class, 'web'])`
```

`route.php` file IS overriden with each generation.  
You should include it in your main route file like that:

```php
$generatedRoutes = __DIR__ . "/OpenApiGenerated/routes.php";
if (file_exists($generatedRoutes)) { // prevents your app and artisan from breaking if there is no autogenerated route file for some reason.
    require $generatedRoutes;
}
```

### 'controllers' => ControllersGenerator::class

Generates Controller class for each non-existing class specified in `x-lg-handler`  
Supports invocable Controllers.  
If several openapi paths point to several methods in one Controller/Handler then the generated class includes all of them.  
If a class already exists it is NOT overriden.  
Controller class IS meant to be modified after generation.  

### 'requests' => RequestsGenerator::class

Generates Laravel Form Requests for DELETE, PATCH, POST, PUT paths  
Destination must be configured with array as namespace instead of string.  
E.g 

```php
'requests' => [
    'namespace' => ["Controllers" => "Requests"]
],
```

This means "Get handler (x-lg-handler) namespace and replace Controllers with Requests in it"  
Form Request class IS meant to be modified after generation. You can treat it as a template generated with `php artisan make:request FooRequest`  
If the file already exists it IS NOT overriden with each generation.  

Form Request class name is `ucFirst(operationId)`. You can override it with `x-lg-request-class-name`  
You can skip generating form request for a give route with `x-lg-skip-request-generation: true` directive.  

### 'enums' => EnumsGenerator::class

Generates Enum class only for enums listed in `oas3->components->schemas`.  
Your need to specify `x-enum-varnames` field in each enum schema. The values are used as enum constants' names.  
Destination directory is cleared before generation to make sure all unused enum classes are deleted.  
Enums generator does NOT support `allOf`, `anyOf` and `oneOf` at the moment.

### 'pest_tests' => PestTestsGenerator::class

Generates Pest test file for each `x-lg-handler`  
You can exclude oas3 path from test generation using `x-lg-skip-tests-generation: true`.  
If a test file already exists it is NOT overriden.  
Test file class IS meant to be modified after generation.

### 'resources' => ResourcesGenerator::class

Generates Resource file for `x-lg-handler`  
Resource properties are generated relative to field in response, which can be set in the config
```php
'resources' => [
    'response_key' => ['data']
],
```
You can also specify `response_key` for endpoint using `x-lg-resource-response-key: data`  
When specifying `response_key`, you can use the "dot" syntax to specify nesting, for example `data.field`  
You can exclude resource generation using `x-lg-skip-resource-generation: true`.  
If a resource file already exists it is NOT overriden.  
Resource file contains a set of fields according to the specification. 
You also need to specify mixin DocBlock to autocomplete resource.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

### Testing

1. composer install
2. npm i
3. composer test

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


