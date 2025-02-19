<?php

namespace Drupal\Tests\config_pr\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Site\Settings;

/**
 * Helper class for Config PR tests.
 */
class Utils {

  /**
   * Exports configuration to files.
   *
   * @param string $configName
   *   The name of the configuration to export.
   *
   * @return bool
   *   TRUE if export was successful, FALSE otherwise.
   *
   * @throws \Exception
   *   If the configuration cannot be exported.
   */
  public static function exportConfig($configName) {
    // Set up the sync directory.
    $config_sync_directory = Settings::get('config_sync_directory', NULL);

    // Ensure the directory exists.
    if (!file_exists($config_sync_directory)) {
      mkdir($config_sync_directory, 0777, TRUE);
    }

    // Get the configuration from active storage.
    $config = \Drupal::config($configName);
    if (!$config) {
      throw new \Exception("Configuration $configName not found.");
    }

    // Create a file storage for the target directory.
    $storage = new FileStorage($config_sync_directory);

    // Write the configuration to file.
    $data = $config->get();
    if (!$data) {
      throw new \Exception("Configuration $configName is empty.");
    }

    return $storage->write($configName, $data);
  }

}
