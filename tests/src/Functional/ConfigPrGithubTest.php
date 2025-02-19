<?php

namespace Drupal\Tests\config_pr\Functional;

use Drupal\Tests\BrowserTestBase;

const REPO_URL = 'https://github.com/' . getenv('REPO_OWNER') . '/' . getenv('REPO_NAME') . '.git';
const REPO_OWNER = getenv('REPO_OWNER');
const REPO_NAME = getenv('REPO_NAME');

/**
 * Tests the Configuration Pull Request GitHub module functionality.
 *
 * @group config_pr
 */
class ConfigPrGithubTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_pr',
    'config_pr_github',
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
   * Tests the GitHub configuration workflow.
   */
  public function testGithubConfiguration() {
    // Configure GitHub token in user profile.
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    
    $edit = [
      'field_config_pr_auth_token[0][value]' => getenv('GITHUB_TOKEN'),
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved');

    // Configure the GitHub repository settings.
    $this->drupalGet('admin/config/development/configuration/pull_request');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Repo configuration is missing');
    $this->clickLink('configuration page');

    $edit = [
      'repo_controller' => 'config_pr_github.repo_controller.github',
      'repo_url' => REPO_URL,
      'repo_owner' => REPO_OWNER,
      'repo_name' => REPO_NAME,
      'message_create' => 'New config @config_name.yml',
      'message_delete' => 'Del config @config_name.yml',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved');

    // Verify the configuration is saved correctly.
    $this->drupalGet('admin/config/development/configuration/pull_request');
    $this->assertSession()->fieldValueEquals('repo_controller', 'config_pr_github.repo_controller.github');
    $this->assertSession()->fieldValueEquals('repo_url', REPO_URL);
    $this->assertSession()->fieldValueEquals('repo_owner', REPO_OWNER);
    $this->assertSession()->fieldValueEquals('repo_name', REPO_NAME);
    $this->assertSession()->fieldValueEquals('message_create', 'New config @config_name.yml');
    $this->assertSession()->fieldValueEquals('message_delete', 'Del config @config_name.yml');
  }

}
