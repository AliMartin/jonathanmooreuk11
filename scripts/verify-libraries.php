<?php

/**
 * @file
 * Verify Composer library paths and runtime assets after installation.
 *
 * Run: ddev exec php scripts/verify-libraries.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Composer\InstalledVersions;

$root = dirname(__DIR__);
$paths = [];
$failures = [];
foreach (['bower-asset', 'npm-asset', 'drupal-library'] as $type) {
  foreach (InstalledVersions::getInstalledPackagesByType($type) as $package) {
    $path = realpath(InstalledVersions::getInstallPath($package) ?? '');
    if ($path === FALSE || !is_dir($path) || !str_starts_with($path, $root . '/libraries/')) {
      $failures[] = 'Library install directory missing or outside libraries/: ' . $package;
      continue;
    }
    if (isset($paths[$path])) {
      $failures[] = 'Install path collision: ' . $paths[$path] . ' and ' . $package;
    }
    $paths[$path] = $package;
    printf("%s %s: %s\n", $package, InstalledVersions::getPrettyVersion($package), substr($path, strlen($root) + 1));
  }
}

// These paths are consumed by the site's Drupal modules.
$assets = [
  'blazy/blazy.min.js',
  'fitvids/jquery.fitvids.js',
  'fontawesome/js/all.min.js',
  'fontawesome/css/all.min.css',
  'fontawesome/metadata/categories.yml',
  'fontawesome/metadata/icons.yml',
  'jquery/dist/jquery.min.js',
  'jquery-bower/dist/jquery.min.js',
  'mmenu/dist/mmenu.js',
  'mmenu/dist/mmenu.css',
  'nouislider/nouislider.min.js',
  'nouislider/nouislider.min.css',
  'slick-carousel/slick/slick.min.js',
  'slick-carousel/slick/slick.css',
  'splide/dist/js/splide.min.js',
  'splide/dist/css/splide.min.css',
  'superfish/dist/js/superfish.js',
];
foreach ($assets as $asset) {
  if (!is_file($root . '/libraries/' . $asset)) {
    $failures[] = 'Missing runtime asset: ' . $asset;
  }
}
foreach (glob($root . '/libraries/*', GLOB_ONLYDIR) as $path) {
  if (!isset($paths[realpath($path)])) {
    $failures[] = 'Unmanaged library directory: ' . basename($path);
  }
}

if ($failures) {
  throw new RuntimeException(implode("\n", $failures));
}
printf("Library checks passed: %d packages, %d runtime assets.\n", count($paths), count($assets));
