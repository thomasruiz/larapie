# larapie
Expose your models through an api in a minute!

So that's still in "beta", but not for long, 'coz it's working pretty well.

## Requirements

- PHP 5.6
- Laravel 5.1

## Installation

Install from composer:

```
composer require thomasruiz/larapie
```

After installing, add the service provider to your `config/app.php` file

```php
Larapie\LarapieServiceProvider::class,
```

Add the default `larapie.php` configuration file

```
php artisan vendor:publish --provider=Larapie\\LarapieServiceProvider
```

## Usage

### The configuration file

```php
// config/larapie.php
<?php

return [
  // Group configuration (optional)
  'group' => [
    'as'         => 'api.',
    'domain'     => 'api.domain.com',
    'middleware' => 'api',
  ],

  'resources' => [
    // Generates:
    // GET /users
    // GET /users/{user}
    // POST /users
    // PUT /users/{user}
    // DELETE /users/{user}
    'users' => App\User::class,
    
    // Relationship
    'users.foos' => App\Foo::class,
    
    // More complex example
    'foos' => [
      // Mandatory
      'model' => App\Foo::class,
      
      // All the rest is optional
      
      'disable_routing' => true, // default to false
      'request' => App\Http\Requests\FooRequest::class, // will run for routes store and update
      'requests' => [
        // Further customization
        'store' => App\Http\Requests\Foo\StoreRequest::class,
        'update' => App\Http\Requests\Foo\UpdateRequest::class,
      ],
      'router_options' => [
        'only' => ['index', 'show'],
        'middleware' => ['auth'],
      ],
    ],
    
    // WON'T WORK, 'bar' model is unknown
    'bar.foo' => App\Foo::class,
  ],
];
```

And everything is ready!


## Checklist

- [ ] API versioning
- [ ] JWT bundled in
- [ ] Pagination, ordering and filters on `index` route
- [ ] Use of Laravel Policies
- [ ] Custom error messages
