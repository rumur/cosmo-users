# [READ ONLY] WordPress Cosmo Users Plugin.

### Author: Ruslan Murarov.

### Minimum Requirements:
 - PHP: 8.1+
 - WordPress: 6.5+
 - node: 20.0+
 - composer: 2.0+

## Overview

This is a plugin that features OOP and SOLID principles of engineering to provide a future-proof approach for scaling the application. 
This means that all modules are not highly coupled but coherent and dependent only on an abstraction.

The development environment of this plugin is based on the [wp-env](https://www.npmjs.com/package/@wordpress/env).

For managing plugin’s modules/components, we use an internal implementation of a DI container that implements PSR-11 standards and can be effortlessly swapped with another 3rd party container implementation e.g Illuminate/Container if needed.

For delivering fast 3rd party API consumption, this plugin possesses a [Concurrent](./src/Support/Http/README.md) client to dispatch requests, leveraging modern PHP8.1 features like [Fibers](https://php.net/fibers).
While it uses `Fibers` as a way of managing requests, it still supports WordPress internal HTTP functionality and all requests just a regular `wp_(safe_)remote_(get|post)` functions; 
therefore, it keeps a known approach to all WordPress developers but enhances the performance of the plugin.

When we’re dealing with 3rd party API calls, it’s always a good idea to use a caching layer to provide a better user experience and to avoid limitations from an API provider side that can significantly reduce the cost of using it.

For a caching layer in this plugin, we use an internal WordPress caching system, which will allow us to leverage any drop-in solution on a hosting provider, like Memcache or Redis. 
It provides a core functionality via a [Cache interface](./src/Support/Cache/README.md) that, in its turn, enhances a [PSR-6](https://www.php-fig.org/psr/psr-6) simple cache standard and 
can be effortlessly swapped with any other caching implementation out there, without a codebase being refactored.

The next thing when you work with the 3rd party APIs you have to do is to introduce a data normalization layer.
This layer provides overall data integrity and cleans up any potential vulnerabilities that might be sent from the other side.

In the plugin’s context, we have a set of two interfaces, [Transformer](./src/Transformer.php) and [DataTransformer](./src/DataTransformer.php),
which are both being used to create this layer. For performing all the work, we bring in the support of a well known 3rd party package - [Fractal](https://fractal.thephpleague.com/), from a PHP League.

When we bring in a 3rd party package like this, we have to protect our codebase from being directly used, 
as it will make it harder to replace this package in the future, in case we no longer like it or for any other reason.

To protect the exposure of this package, we use a [FractalAdapter](./src/Support/Transformer/FractalAdapter.php) which in its turn implements [Transformer](./src/Transformer.php) interface described above, 
thus making it safe for use inside the codebase via DI.

### Extension

Plugin can be extended via so called - Modules. Modules are nothing else but simple classes that implement [Module](./src/Module.php) interface. 
Each module not limited to add its own submodules. This way it easily allows to extend the plugin functionality in the future and keeps the codebase clean and maintainable.

### Frontend

The Frontend part of this plugin is being provided via [WordPress Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/) and leverages declarative way of writing the code, 
and connecting both worlds Backend and Frontend.

The main list of users delivered via simple HTML table, which can be seen by visiting this url http://localhost:8888/cosmo-users or http://localhost:8888/?cosmo-users=1 if a pretty permalinks are disabled.

Each row of this table represents the short preview of the user’s data. Upon clicking on the row, the app opens up the modal where we fetch data for a single user via AJAX request. 
To close this modal, you can click either on the “x” button in the top right corner or press the _ESC_ button. 
On slow connections, users might close the modal before data gets fetched from a server. 
For this particular use case, we have an “abort” mechanism for requests that started but don’t have to be fulfilled.

## Usage

It includes the following features:
- PHP Code Styles and Linting, also supports partial linting of staged files, upon commit.
- PHP Rector
- PHP Unit Testing

To install all the dependencies, run the following command:

```bash
$ npm install
```

To **build the plugin's assets**, run the following command:

```bash
$ npm run assets:build
```

> [!WARNING]
> The plugin's assets should be built 👆🏻before use, otherwise, 
> the plugin will throw an error saying that the assets are missing.

To start the local env, run the following command:

```bash
$ npm run start
```

Once the env starts you can access the WordPress admin panel by visiting [http://localhost:8888/wp-admin](http://localhost:8888/wp-admin) with the following credentials:
- Username: `admin`
- Password: `password`

To stop the local env, run the following command:

```bash
$ npm run stop
```

To run the PHP Code Style and Linting, run the following command:

```bash
$ npm run lint
```

To run the PHP Unit Testing, run the following command:

```bash
$ npm run test
```

## [Container](src/Support/Container/README.md)

The `Container` class is a simple implementation of the [PSR-11](https://www.php-fig.org/psr/psr-11) container interface, for more details check the [Container README](src/Support/Container/README.md).

## [Templates](src/Support/Template/README.md)

The `Template\Manager` class is responsible for locating and rendering template files within the Cosmo Users plugin. 
It acts as a composite locator, utilizing multiple locators to find the desired template file.

> [!TIP]
> To override the template file, you can create a new template file in your theme directory with the same name as the template file you want to override,
> and placed it inside the one of the following folders: `templates/cosmo-users`, `templates-parts/cosmo-users` or `parts/cosmo-users`.
> 
> Worth to mention that the template file w/i the child theme gets the most priority among all the other locations.

## [Http](src/Support/Http/README.md)

The `Http\Concurrent` class is responsible for resolving multiple HTTP requests concurrently.
To add a new Http Client you can create a new class that implements the `Http\Client` interface.
For more details check the [Http README](src/Support/Http/README.md).

## [Cache](src/Support/Cache/README.md)

The `Cache` instance is responsible for managing the cache data.
It provides a simple way to store and retrieve data from the cache.
For more details check the [Cache README](src/Support/Cache/README.md).

## [Transformer](src/Support/Transformer/README.md)

The `Transformer` and `DataTransformer` interfaces facilitate the transformation of data from one format to another.
To add a new transformer you can create a new class that implements the `DataTransformer` interface.

Example:
```php
use Rumur\WordPress\Cosmo\Users\Support\Transformer\DataTransformer;

class MyTransformer implements DataTransformer
{
    public function transform($data)
    {
        return [
            'id' => absint($data['id'] ?? 0),
            'fullName' => esc_attr($data['username_full_name'] ?? 'n/a'),
            'email' => esc_attr($data['email'] ?? 'n/a'),
            'userPhone' => esc_attr($data['user_phone'] ?? 'n/a'),
            'website' => esc_attr($data['website'] ?? 'n/a'),
            'description' => wp_kses_post($data['description'] ?? 'n/a'),
        ];
    }
}

$transformedCollection = $plugin->get(\Rumur\WordPress\CosmoUsers\Transformer::class)->collection([...], new MyTransformer());
$transformedItem = $plugin->get(\Rumur\WordPress\CosmoUsers\Transformer::class)->item([...], new MyTransformer()); 
```

## License
  This package is licensed under the MIT License - see the [LICENSE.md](https://github.com/rumur/cosmo-users/blob/master/LICENSE) file for details.
