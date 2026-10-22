# Configuration export maintenance

## Fresh export — 21 October 2026

Rebuilt the project-root `sync/` folder from the local Drupal 11.4.11 site's
active configuration, following the Drupal 11 upgrade.

1. Backed up the complete previous `sync/` folder and the original
   `sites/default/settings.php` outside the web root, at
   `/tmp/jonathanmooreuk11-sync-20261021-184058/` on the host.
   `sync-before.tar.gz` contains the old folder, including hidden files;
   `settings.php.before` contains the original settings. The directory is
   accessible only to its owner. These temporary backups are not durable.
2. Corrected the configured sync directory. Drupal had been using
   `sites/default/files/sync`, although the project's exported configuration
   is stored at the root in `sync/`. Added this setting before the DDEV
   settings include in `sites/default/settings.php`:

   ```php
   // Use the project's versioned configuration export directory.
   $settings['config_sync_directory'] = $app_root . '/sync';
   ```

   `settings.php` is ignored by Git, so other environments need this setting
   in their own settings file as well.
3. Removed all 443 old YAML files and the stray `.DS_Store` from `sync/`.
   Retained `.htaccess` throughout to preserve the existing Apache access
   protection.
4. Ran `ddev drush config:export --yes`, exporting all 443 active
   configuration objects afresh into `/var/www/html/sync`.
5. Ran `ddev drush config:status` and compared active and sync storage using
   Drupal's `StorageComparer`. Both confirmed that the export matches the
   database, with no outstanding differences or additional collections.

The fresh export contains 443 YAML files and `.htaccess`. Compared with the
previous export, 431 YAML files are identical and 12 differ; no YAML names
were added or removed. The refreshed files capture configuration already
present in the database:

- `backup_migrate.backup_migrate_schedule.daily_schedule.yml`: explicit
  encryption defaults.
- The six `pathauto.pattern.*` files for credit, gallery, news, review,
  testimonial and video: current patterns and dependency metadata.
- `tb_megamenu.menu_config.main__jm25.yml`: current menu configuration.
- `views.view.gallery_page.yml`, `views.view.news.yml`,
  `views.view.testimonials.yml` and `views.view.video_listing.yml`: current
  menu ordering and exposed-filter settings.

This operation exported the local database configuration; it did not import
configuration, update module versions, or deploy to production.

## Repeat this procedure

Run from the project root. First confirm that the active database contains
the configuration you want to keep and that Drupal points at this project's
`sync/` directory:

```sh
ddev drush status --fields=drupal-version,db-status,bootstrap,config-sync
```

Then back up, purge the old export while retaining its protection file, and
export the active configuration:

```sh
config_backup="$(mktemp -d /tmp/jonathanmooreuk11-sync-XXXXXX)"
tar -czf "$config_backup/sync-before.tar.gz" sync
find sync -mindepth 1 ! -name .htaccess -delete
ddev drush config:export --yes
ddev drush config:status
```

The final command should report `No differences between DB and sync
directory`. Review the file changes before committing or deploying the
export. Do not run a configuration import as part of refreshing an export.

To recover the previous exported files, extract `sync-before.tar.gz` from
the project root. That restores files only; importing them into Drupal is
a separate operation that changes active configuration.
