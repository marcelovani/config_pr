<?php

namespace Drupal\Tests\config_pr\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Site\Settings;

/**
 * Helper class for Config PR tests.
 */
class Utils {

  /**
   * Exports configuration to a specific directory.
   *
   * @param string $config_name
   *   The configuration name to export (e.g., 'system.site').
   */
  public static function exportConfig($config_name) {
    // Set up the sync directory
    $config_sync_directory = Settings::get('config_sync_directory', NULL);

    // Ensure the directory exists
    if (!file_exists($config_sync_directory)) {
      mkdir($config_sync_directory, 0777, TRUE);
    }

    // Get the configuration from active storage
    $config = \Drupal::config($config_name);
    if (!$config) {
      throw new \Exception("Configuration '$config_name' not found.");
    }

    // Create a file storage for the target directory
    $storage = new FileStorage($config_sync_directory);

    // Write the configuration to file
    $storage->write($config_name, $config->get());
  }

}
