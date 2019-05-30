# Drupal Webforms Wrapper

This project provides composer tooling around the [Drupal Webforms
module](https://www.drupal.org/project/webforms).

## Usage

In order to install the dependencies of this project, you will need to add two
upstream repositories to your project as well as some custom composer package
definitions. The following commands are available as the [setup script](#setup-script)
below. You can then add the project to you composer project as normal.

### Requirements Overview

You will need to add the two following composer repositories:

- Drupal 8 - https://packages.drupal.org/8 - Drupal 8 modules
- Asset Packagist - https://asset-packagist.org - Mirror of NPM and Bower libraries
  for composer

You will also need to add the following custom packages. For package configuration,
please view this projects `composer.json`.

-  ckeditor/link - [CKEditor 4 Link Plugin](https://ckeditor.com/cke4/addon/link)
-  ckeditor/image - [CKEditor 4 Image Plugin](https://ckeditor.com/cke4/addon/image)
-  ckeditor/autogrow - [CKEditor 4 Autogrow Plugin](https://ckeditor.com/cke4/addon/autogrow)
-  ckeditor/fakeobjects - [CKEditor 4 Fake Objects Plugin](https://ckeditor.com/cke4/addon/fakeobjects)

### Setup Script

```
php -r "copy('https://raw.githubusercontent.com/roygoldman/drupal-webform-wrapper/8.x/drupal-webform-wrapper-setup.php', 'dww-setup.php');"
php dww-setup.php
php -r "unlink('dww-setup.php');"
```

Once the script is run, you will need to include this project in your repo,
using the following.

### Add `drupal-webform-wrapper`

To use this project, you will need to include the repository configuration from
this project's composer.json in your project. This allows for the downloading
and discovery of the required front-end dependencies.

Once the repositories configuraition is added, simply require this package in
your composer project to download the libraries and config.

```
composer require roygoldman/drupal-webform-wrapper
```

### Advanced Usage

By default the script will add new packages definitions but not wipe out old
versions. You can specify the `--upstream` or `-u` argument to the script
to replace existing package definitions instead of merging versions.

```
php drupal-webform-wrapper-setup.php --upstream
```

## Support

Support for this project can be found on [GitHub](https://github.com/roygoldman/drupal-webform-wrapper).
