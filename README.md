# [READ ONLY] WordPress Cosmo Users Plugin.

### Minimum Requirements:
 - PHP: 8.1+
 - WordPress: 6.6+
 - node: 20.0+
 - composer: 2.0+

## Installation

```composer require rumur/cosmo-users```

## Usage

This package is a WordPress starter plugin that provides a set of tools to help you build your WordPress plugin, 
it's based on official [wp-env](https://www.npmjs.com/package/@wordpress/env).

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

## License
  This package is licensed under the MIT License - see the [LICENSE.md](https://github.com/rumur/cosmo-users/blob/master/LICENSE) file for details.
