# Symbiotic on Workerman RoadRunner

## Installation

```
composer require symbiotic/workerman
```

## Usage

1. Initialize the framework according to the
   documentation: [Symbiotic quick start](https://github.com/symbiotic-php/full)!
2. Replace the session storage with a file storage (instructions below).
3. Run in console `php ./index.php workerman:worker start --host=127.0.0.1 --port=80`.
4. Request http://127.0.0.1/symbiotic/ (there will be an error #7623)


### Configuring Session Storage

If you are using a native session driver, you need to replace it with another one, the framework already has a file
driver for storing sessions, you need to switch to it:

```php
$config = include $base_path.'/vendor/symbiotic/full/src/config.sample.php';

// Replace section in symbiotic core configuration
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

//// loading Core...
```






