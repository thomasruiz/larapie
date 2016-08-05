# larapie
Expose your models through an api in a minute!

So that's still in "beta", but not for long, 'coz it's working pretty well.

## Installation

Install from composer:

```
composer require thomasruiz/larapie
```

After installing, add the service provider to your `config/app.php` file

```php
Larapie\LarapieServiceProvider::class
```

Add the default `larapie.php` configuration file

```
php artisan vendor:publish --provider=Larapie\\LarapieServiceProvider
```

## Usage

### The configuration file

```php
// config/larapie.php

return [
  'resources' => [
    // Generates:
    // GET /user
    // GET /user/{id}
    // POST /user
    // PUT /user/{id}
    // DELETE /user/{id}
    'user' => App\User::class,
    
    // More complex example
    'foo' => [
      // Mandatory
      'model' => App\Foo::class,
      
      // All the rest is optional
      
      'disable_routing' => true, // default to false
      'router_options' => [
        'only' => ['index', 'show'],
        'middleware' => ['auth'],
      ],
    ]
  ]
];
```

And everything is ready!
