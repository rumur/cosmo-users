# [READ ONLY] WordPress Cosmo Users Plugin.

### Author: Ruslan Murarov.

### Minimum Requirements:
 - PHP: 8.1+
 - WordPress: 6.6+
 - node: 20.0+
 - composer: 2.0+

## Installation

```composer require rumur/cosmo-users```

## Usage

This package is a demo WordPress plugin that provides a simple way to show users from a third party APIs in our case https://jsonplaceholder.typicode.com/users service.
The development environment of this plugin is based on the [wp-env](https://www.npmjs.com/package/@wordpress/env).

It includes the following features:
- PHP Code Styles and Linting
- PHP Rector
- PHP Unit Testing

To install all the dependencies, run the following command:

```bash
$ npm install
```

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

## [Container](src/Support/Container/README.md)

The `Container` class is a simple implementation of the PSR-11 container interface, for more details check the [Container README](src/Support/Container/README.md).

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

$transformedCollection = $plugin->transformer->collection([...], new MyTransformer());
$transformedItem = $plugin->transformer->item([...], new MyTransformer()); 
```

## License
  This package is licensed under the MIT License - see the [LICENSE.md](https://github.com/rumur/cosmo-users/blob/master/LICENSE) file for details.
