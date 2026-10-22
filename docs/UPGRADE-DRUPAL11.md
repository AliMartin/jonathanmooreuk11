# Drupal 11 upgrade

The local site was upgraded from Drupal 10.6.20 to Drupal 11.4.11.
The web root remains the project root. Use PHP 8.3 or later, Composer 2.7
or later, and a database supported by Drupal 11. Local DDEV uses PHP 8.3
and MariaDB 11.8, with project type `drupal11`.

## Dependencies

- Drupal core packages now require `^11`; Drush requires `^13`.
- Composer Installers now uses version 2.
- Responsive Menu uses stable 4.4.5, which supports Drupal 11 and the
  existing mmenu 8 library, replacing 5.0.0-alpha2.
- Background Image remains at 2.0.2 in `composer.lock`. Its development
  branches introduce a Media/Context migration that this site does not use.
  `patches/background-image-drupal11.patch` updates its core declaration,
  removes Symfony's removed container-aware APIs, adds required return
  types, and replaces removed file URL and GD APIs. The patch is applied by
  Composer Patches 2, with its checksum recorded in `patches.lock.json`.
  Composer Drupal Lenient relaxes package metadata **only** for
  `drupal/background_image`; the patch supplies the actual compatibility fixes.
  Keep the patch and both lock files with the deployment. Reassess the patch
  when updating Background Image; do not switch to a development branch
  without planning its data migration.

Drupal's database updates installed the `search_node` and `search_help`
submodules and migrated editor, block, search, performance and Views settings.
The matching upgrade changes are included in `sync/`.

After the upgrade, `sync/` was purged and rebuilt with a complete active
configuration export. See [configuration export maintenance](CONFIG-EXPORT.md)
for the export-path correction, backup location, commands and verification.

## Deploy to another environment

Back up that environment's database, uploaded files and current code first.
Run its existing Drupal 10 database updates before replacing the code. Use
the committed lock files rather than resolving fresh versions on the server.

```sh
vendor/bin/drush updatedb --yes
vendor/bin/drush state:set system.maintenance_mode 1
vendor/bin/drush cache:rebuild
# Deploy the upgraded code, composer.json, composer.lock, patches/ and patches.lock.json.
composer install --no-interaction --optimize-autoloader
vendor/bin/drush updatedb --yes
vendor/bin/drush cache:rebuild
# Import sync/ only if this environment normally deploys that configuration.
# Check the import diff first; this repository's sync path must be configured.
vendor/bin/drush state:set system.maintenance_mode 0
vendor/bin/drush cache:rebuild
```

Drush is an existing development dependency, so this sequence includes dev
packages. A deployment using `--no-dev` needs a separately available,
compatible Drush. Do not import older Drupal 10 configuration after the
database updates.

## Verification

```sh
ddev composer validate --strict
ddev composer check-platform-reqs
ddev composer audit
ddev drush updatedb:status
ddev drush core:requirements --severity=2
ddev drush php:script scripts/verify-drupal11.php
```

The verification script checks installed extension declarations, loads and
serializes all background images, resolves their URLs and checks eight
public page responses. It targets `http://localhost` inside DDEV. Interactive
browser testing remains necessary for mobile menus and editor workflows.

The upgrade preserved 239 nodes, 2 users, 266 file records, 52 background
images, 203 media entities and 13 paragraphs. The local copy already had
missing source image files before the upgrade; those assets need to be
restored from the original uploads if required.

Composer found no known security advisories. Its audit reports the existing
abandoned `oomphinc/composer-installers-extender` package (exit code 2), which
is still used to install this site's Bower/npm libraries.

## Local rollback

The pre-upgrade database snapshot is named `before-drupal11`. Restore the
pre-upgrade code and configuration together with the database; restoring
only Composer dependencies cannot reverse database migrations.

Original Composer files and DDEV config are also saved at
`/tmp/jonathanmooreuk11-pre-upgrade/` on the host; temporary files are not
durable backups. The original configuration is also in the pre-upgrade
database snapshot and can be exported after restoring that database.

```sh
# After restoring the pre-upgrade code, Composer files and configuration:
ddev composer install --no-interaction
ddev snapshot restore before-drupal11
ddev drush cache:rebuild
```

Only the local DDEV site has been upgraded; production deployment is separate.

References: [Drupal's upgrade guide](https://www.drupal.org/docs/upgrading-drupal/upgrading-drupal/how-to-upgrade-from-drupal-10-to-drupal-11),
[Responsive Menu 4.4.5](https://www.drupal.org/project/responsive_menu/releases/4.4.5),
[Background Image](https://www.drupal.org/project/background_image).
