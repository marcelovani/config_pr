<?php

namespace Drupal\Tests\config_pr\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\config_pr\Functional\Utils;

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
    'config_pr_github',
    'config_pr_gitlab',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Stores the repo owner.
   * 
   * @var string
   */
  protected $repo_owner;

  /**
   * Stores the repo user.
   * 
   * @var string
   */
  protected $repo_name;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->repo_owner = getenv('REPO_OWNER');
    $this->repo_name = getenv('REPO_NAME');

    // Create and log in our privileged user.
    $this->adminUser = $this->drupalCreateUser([
      'administer configuration pull request',
      'issue configuration pull requests',
      'synchronize configuration',
    ]);
    $this->drupalLogin($this->adminUser);

    // Export initial config.
    $configs_to_export = [
      'system.site',
      'system.theme',
      // Add other configurations you want to export
    ];
    foreach ($configs_to_export as $config_name) {
      Utils::exportConfig($config_name);
    }

    // Get the config factory service.
    $config_factory = \Drupal::service('config.factory');
    // Load the 'system.site' configuration.
    $config = $config_factory->getEditable('system.site');
    // Update the site name to 'foo'.
    $config->set('name', 'Test Site');
    // Save the configuration.
    $config->save();
  }

  public function testConfigPrGithub(): void {
    $this->runTests('github');
  }

  public function testConfigPrGitlab(): void {
    $this->runTests('gitlab');
  }

  /**
   * Tests the config pull request workflow.
   */
  private function runTests(string $type): void {
    $repo_url = 'https://' . $type . '.com/' . $this->repo_owner . '/' . $this->repo_name . '.git';

    // Configure access token in user profile.
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    
    $edit = [
      'field_config_pr_auth_token[0][value]' => getenv(strtoupper($type) . '_TOKEN'),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved');

    // Configure the repository settings.
    $this->drupalGet('admin/config/development/configuration/pull_request');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Repository configuration is missing');
    $this->clickLink('configuration page');

    $edit = [
      'repo_controller' => 'config_pr_' . $type . '.repo_controller.' . $type,
      'repo_url' => $repo_url,
      'repo_owner' => $this->repo_owner,
      'repo_name' => $this->repo_name,
      'message_create' => 'New config @config_name.yml',
      'message_delete' => 'Del config @config_name.yml',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved');

    // Verify the configuration is saved correctly.
    $this->assertSession()->fieldValueEquals('repo_controller', 'config_pr_' . $type . '.repo_controller.' . $type);
    $this->assertSession()->fieldValueEquals('repo_url', $repo_url);
    $this->assertSession()->fieldValueEquals('repo_owner', $this->repo_owner);
    $this->assertSession()->fieldValueEquals('repo_name', $this->repo_name);
    $this->assertSession()->fieldValueEquals('message_create', 'New config @config_name.yml');
    $this->assertSession()->fieldValueEquals('message_delete', 'Del config @config_name.yml');

    // Navigate to Config pull request form.
    $this->clickLink('Pull Requests page');

    $this->assertSession()->pageTextContains('1 changed');
    $this->assertSession()->linkExists('View differences');
    $this->assertSession()->optionExists('source_branch', 'master');

    // Select config.
    $page = $this->getSession()->getPage();
    $page->find('css', '[name="select-system_site"]')->check();
    $this->assertSession()->checkboxChecked('select-system_site');

    // Create pull request.
    $edit = [
      'pr_title' => 'Test PR',
      'pr_description' => 'Automated test PR',
    ];
    $this->submitForm($edit, 'Create Pull Request');
  }

}
