# Symbiotic on Workerman RoadRunner

## Installation

```
composer require symbiotic/workerman
```

## Installation with additional packages

```json
{
  "require": {
    "symbiotic/workerman": "^1.4",
    "symbiotic/auth-login": "^1.4",
    "symbiotic/develop": "^1.4"
  }
}
```
## Usage

1. Initialize the framework according to the
   documentation: [Symbiotic quick start](https://github.com/symbiotic-php/full)!
2. Replace the session storage with a file storage (instructions below) and config.
3. Run in console `php ./index.php workerman:worker start --host=127.0.0.1 --port=80`.
4. Request http://127.0.0.1/symbiotic/ (there will be an error #7623)


### Configuring Index.php

```php


$basePath = dirname(__DIR__);// root folder of the project

include_once $basePath. '/vendor/autoload.php';

$config = include $basePath.'/vendor/symbiotic/full/src/config.sample.php';

// Replace section in symbiotic core configuration
$config['default_host'] = '127.0.0.1';
// for first run 
$config['debug'] = true;
// If you are using a native session driver, you need to replace it with another one,
// the framework already has a file driver for storing sessions, you need to switch to it:
$config['session'] = [
    'driver' => 'file',
    'minutes' => 1200,
    'name' => session_name(),
    'path' => session_save_path(),
    'namespace' => '5a8309dedb810d2322b6024d536832ba',
    'secure' => false,
    'httponly' => true,
    'same_site' => null,
];

// if installed package `symbiotic/auth-login`
$config['auth'] =  [
    'users' =>[
        [
            'login' => 'admin',
            /** @see \Symbiotic\Auth\UserInterface::GROUP_ADMIN  and other groups **/
            'access_group' => \Symbiotic\Auth\UserInterface::GROUP_ADMIN, //admin
            // Password in https://www.php.net/manual/ru/function.password-hash.php
            // algo - PASSWORD_BCRYPT
            'password' => '$2y$10$fblGNBFYBjC9a3L6d0.lle1BoVFdMlMOzN6/NWjqBb8wFlJZt9P8C'//
        ]
    ],
    'base_login' => true, // enabling and disabling login authorization
];

// Basic construction of the Core container
$core = new \Symbiotic\Core\Core($config);

/**
 * When installing the symbiotic/full package, a cached container is available
 * Initialization in this case occurs through the Builder:
 */
$cache = new Symbiotic\Cache\FilesystemCache($config['storage_path'] . '/cache/core');
$core = (new \Symbiotic\Core\ContainerBuilder($cache))
    ->buildCore($config);

// Starting request processing
$core->run();
```







