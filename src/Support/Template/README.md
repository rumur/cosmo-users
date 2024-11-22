# Cosmo Users Plugin Template Manager

## Overview

The `Template\Manager` class is responsible for locating and rendering template files within the Cosmo Users plugin. It acts as a composite locator, utilizing multiple locators to find the desired template file.

## Usage

### Locating a Template

To locate a template file, use the `locate` method. 
This method will search through the registered locators and return the full path to the template file if found.

> [!WARNING]
> If the template file is not possible to locate among provided locations, the method will throw an `TemplateNotFound` exception. 

**Example of usage of the `Template\Manager::locate` class:**
```php
use Rumur\WordPress\CosmoUsers\Support\Template\Manager;
use Rumur\WordPress\CosmoUsers\Support\FileLocator;

$templateManager = new Manager(
    new FileLocator\Theme('templates/cosmo-users', 'template-parts/cosmo-users'),
    new FileLocator\Direct(lookupDir: __DIR__ . '/template-parts')
);

try {
    $templatePath = $templateManager->locate('users-table');
    echo "Template located at: " . $templatePath;
} catch (TemplateNotFound $e) {
    echo "Template not found: " . $e->getMessage();
}
```


### Rendering a Template

To render a template file, use the `render` method.
This method will locate the template file and render it using the provided data.

**Example of usage of the `Template\Manager::render`  class:**

```php

use Rumur\WordPress\CosmoUsers\Support\Template\Manager;
use Rumur\WordPress\CosmoUsers\Support\FileLocator;

$templateManager = new Manager(
    new FileLocator\Theme('templates/cosmo-users', 'template-parts/cosmo-users'),
    new FileLocator\Direct(lookupDir: __DIR__ . '/template-parts')
);

$users = Plugin::instance()->get(Rumur\WordPress\CosmoUsers\Users\ReadService::class)->getUsers();

$output = $templateManager->render('users-table', ['users' => $users]);
echo $output;
```

### Adding a Custom Locator

To add a custom locator, implement the `Rumur\WordPress\CosmoUsers\Support\FileLocator\Locator` interface and pass it to the `Manager` constructor.

> [!NOTE]
> Order of locators matters. The first locator that finds the template file will be used.
> **It means that if a template file has to be changed, the locator that finds it first should be changed, e.g. if it was places w/i either a parent or a child theme** 
