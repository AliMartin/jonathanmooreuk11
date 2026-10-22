<?php

/**
 * @file
 * Read-only upgrade checks. Run: ddev drush php:script scripts/verify-drupal11.php
 */

if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
  exit(1);
}

$failures = [];
if (explode('.', \Drupal::VERSION)[0] !== '11') {
  $failures[] = 'Expected Drupal 11, found ' . \Drupal::VERSION;
}

// Catch incompatible module/theme declarations, including optional submodules.
$extensions = \Drupal::config('core.extension');
foreach (['module', 'theme'] as $type) {
  $list = \Drupal::service('extension.list.' . $type);
  foreach (array_keys($extensions->get($type)) as $name) {
    $info = $list->getExtensionInfo($name);
    if (!\Composer\Semver\Semver::satisfies(\Drupal::VERSION, $info['core_version_requirement'] ?? '*')) {
      $failures[] = 'Incompatible ' . $type . ': ' . $name;
    }
  }
}

// Exercise the patched entity serialization and service dependencies.
\Drupal::service('background_image.manager');
$backgrounds = \Drupal::entityTypeManager()->getStorage('background_image')->loadMultiple();
foreach ($backgrounds as $background) {
  $restored = unserialize(serialize($background));
  if ($restored->id() !== $background->id()) {
    $failures[] = 'Background image serialization failed: ' . $background->id();
  }
  $background->getImageUrl();
}
printf("Drupal %s; %d background images checked.\n", \Drupal::VERSION, count($backgrounds));

$client = \Drupal::httpClient();
foreach (['/', '/actor', '/writer', '/director', '/news', '/contact', '/gallery/2graves', '/user/login'] as $path) {
  $response = $client->get('http://localhost' . $path, ['http_errors' => FALSE]);
  $status = $response->getStatusCode();
  printf("%d %s\n", $status, $path);
  if ($status !== 200 || str_contains((string) $response->getBody(), 'The website encountered an unexpected error')) {
    $failures[] = 'Page failed: ' . $path;
  }
}

if ($failures) {
  throw new \RuntimeException(implode("\n", $failures));
}
echo "Upgrade checks passed.\n";
