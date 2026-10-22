# Composer-managed libraries

All third-party libraries in the project-root `libraries/` directory are
installed by Composer. Commit `composer.json` and `composer.lock`; the
generated library files are ignored by Git.

## Install

From the project root:

```sh
ddev composer install
ddev drush cache:rebuild
ddev exec php scripts/verify-libraries.php
```

On a server without DDEV, use `composer install` and the equivalent PHP and
Drush commands. Use `install` when deploying so versions come from the lock
file. No manual downloads, npm install, or asset build step are required.

## Packages and paths

Versions below are those locked on 21 October 2026.

| Package | Version | Install directory |
| --- | --- | --- |
| `npm-asset/blazy` | 1.8.2 | `libraries/blazy` |
| `bower-asset/fitvids` | 1.2.0 | `libraries/fitvids` |
| `npm-asset/fortawesome--fontawesome-free` | 6.4.2 | `libraries/fontawesome` |
| `npm-asset/jquery` | 4.0.0 | `libraries/jquery` |
| `bower-asset/jquery` | 4.0.0 | `libraries/jquery-bower` |
| `bower-asset/mmenu` | 8.5.24 | `libraries/mmenu` |
| `drupal/nouislider_js` | 15.8.1 | `libraries/nouislider` |
| `npm-asset/slick-carousel` | 1.8.1 | `libraries/slick-carousel` |
| `npm-asset/splidejs--splide` | 4.1.4 | `libraries/splide` |
| `npm-asset/superfish` | 1.7.10 | `libraries/superfish` |

Asset Packagist supplies the npm and Bower packages. The existing Composer
Installers Extender plugin installs their package types into `libraries/`.
Specific mappings in `extra.installer-paths` come before the general mapping:

- Font Awesome and Splide keep the paths expected by their Drupal modules.
- FitVids requires the Bower jQuery package, while Superfish requires the npm
  jQuery package. They previously shared `libraries/jquery`, allowing one
  installation to overwrite the other. Bower jQuery now has its own directory;
  npm jQuery retains the existing `libraries/jquery` path. This does not
  change Drupal core's jQuery library or attach an additional copy to pages.
- noUiSlider remains a transitive dependency of Better Exposed Filters. Its
  package declares the `nouislider` installer name.

Font Awesome and Splide use `~6.4.2` and `~4.1.4` constraints respectively,
keeping them on their existing minor release lines. The exact installed
versions are recorded in `composer.lock`.

## Migration record — 21 October 2026

Added the two previously manual libraries, Font Awesome 6.4.2 and Splide
4.1.4, to Composer requirements. Added explicit install-path mappings,
separated the two jQuery packages, and ignored `/libraries/` in `.gitignore`.
No existing package versions were changed or removed.

The original library directory and Composer files were preserved outside
the web root at `/tmp/jonathanmooreuk11-libraries-20261021-184439/` on the
host. `libraries-before/` contains the original files. This temporary backup
is not durable.

Moved the complete original directory aside and ran Composer with an absent
`libraries/` folder. Composer installed all ten library packages, including
their runtime assets. Compared 15 key files across the nine original
directories with the backup; all were byte-for-byte identical. A subsequent
`composer install --dry-run` reported nothing to install, update or remove.

Validation also included strict Composer validation, runtime asset/path
checks, a Drupal cache rebuild, eight page-response checks and Drupal's
requirements report. Interactive browser behaviour was not tested.

For deliberate version changes, update only the intended packages, inspect
the lock-file diff, clear Drupal caches and run both checks:

```sh
ddev exec php scripts/verify-libraries.php
ddev drush php:script scripts/verify-drupal11.php
```

Keep custom project code outside the ignored directory. Composer may replace
library files when reinstalling or updating packages.

Reference: [Drupal's Composer dependency documentation](https://www.drupal.org/docs/develop/using-composer/manage-dependencies).
