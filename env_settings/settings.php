<?php

$settings['hash_salt'] = 'PJWEzTRbGkxVdOqsi-XR7M7Fjetz6cgu6gF8QtPmtqWYMnuDesJjtiAA5dd0sfY9abqlgILdjw';

$settings['update_free_access'] = FALSE;

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

$databases['default']['default'] = array (
  'database' => 'd8',
  'username' => 'd8',
  'password' => 'd8',
  'prefix' => '',
  'host' => 'mysql',
  'port' => '',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

$config_directories['sync'] = '../config/sync';
$settings['install_profile'] = 'minimal';
$conf['sanitize_input_logging'] = 1;
$settings['file_private_path'] = 'private';
