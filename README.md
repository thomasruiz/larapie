# larapie
Expose your models through an api in a minute!

So that's still in "beta", but not for long, 'coz it's working pretty well.

## Installation

Install from composer:

`composer require thomasruiz/larapie`

After installing, add the service provider to your `config/app.php` file

`Larapie\LarapieServiceProvider::class`

Add the default `larapie.php` configuration file

`php artisan vendor:publish --provider=Larapie\\LarapieServiceProvider`
