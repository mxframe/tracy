## [Nette Tracy](https://github.com/nette/tracy.git) for Laravel 5

Better Laravel Exception Handler

## Features
- Visualization of errors and exceptions
- Debugger Bar (ajax support @v1.5.6)
- Exception stack trace contains values of all method arguments.

## Online Demo
[Demo](https://cdn.rawgit.com/recca0120/laravel-tracy/master/docs/tracy-exception.html)

## Installing

To get the latest version of Laravel Exceptions, simply require the project using [Composer](https://getcomposer.org):

```bash
composer require recca0120/laravel-tracy --dev
```

Instead, you may of course manually update your require block and run `composer update` if you so choose:

```json
{
    "require-dev": {
        "recca0120/laravel-tracy": "^1.8.14"
    }
}
```

Include the service provider within `config/app.php`. The service povider is needed for the generator artisan command.

```php
'providers' => [
    ...
    Recca0120\LaravelTracy\LaravelTracyServiceProvider::class,
    ...
];
```

publish

```bash
php artisan vendor:publish --provider="Recca0120\LaravelTracy\LaravelTracyServiceProvider"
```

if you see Route [tracy.bar] not defined. pleace run `artisan route:clear` once

```bash
artisan route:clear
```