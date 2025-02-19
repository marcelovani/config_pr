<?php

namespace Drupal\Tests\config_pr\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Configuration Pull Request module functionality.
 *
 * @group config_pr
 */
class ConfigPrTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_pr',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in our privileged user.
    $this->adminUser = $this->drupalCreateUser([
      'administer configuration pull request',
      'issue configuration pull requests',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the configuration form.
   */
  public function testConfigurationForm() {
    // Access the main configuration page.
    $this->drupalGet('admin/config/development/configuration/pull_request');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Pull Request');

    // Access the settings page.
    $this->drupalGet('admin/config/development/configuration/pull_request/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Pull Request Settings');
  }

}
