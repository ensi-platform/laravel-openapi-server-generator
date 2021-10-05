# Laravel OpenApi Server Generator

Deprecated, use https://github.com/ensi-platform/laravel-openapi-server-generator instead

Пакет для Laravel, который генерирует Dto модели при помощи [OpenApi Generator](https://openapi-generator.tech/).

## Зависимости:
1. Java 8 и выше.
2. npm 5.2 и выше.

## Установка:
1. `composer require --dev greensight/laravel-openapi-server-generator`
2. `php artisan vendor:publish --provider="Greensight\LaravelOpenapiServerGenerator\OpenapiServerGeneratorServiceProvider"` - копирует конфиг генератора в конфиги приложения

## Запуск:
Перед запуском убедиться, что структура описания апи соответствует [этим требованиям](https://github.com/greensight/laravel-openapi-server-generator/blob/master/docs/api_schema_requirements.md).

Запускать командой: `php artisan openapi:generate-server`

После успешного выполнения в директории `app/<appDir> (указывается в конфиге)` должны появиться следующие файлы:
1. Dto - директория со всеми Dto апи;
2. ObjectSerializer.php и Configuration.php - вспомогательные файлы для Dto;

## Ограничения

Пакет на данный момент не поддерживает генерацию в Windows окружении.

