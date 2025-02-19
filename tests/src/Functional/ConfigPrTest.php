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
   * The admin user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The repository owner.
   *
   * @var string
   */
  protected $repoOwner;

  /**
   * The repository name.
   *
   * @var string
   */
  protected $repoName;

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
   * {@inheritdoc}
   *
   * @return void
   *   Nothing to return.
   */
  protected function setUp(): void {
    parent::setUp();

    // Get environment variables.
    $this->repoOwner = getenv('REPO_OWNER');
    $this->repoName = getenv('REPO_NAME');

    // Create and log in our privileged user.
    $this->adminUser = $this->drupalCreateUser([
      'administer configuration pull request',
      'issue configuration pull requests',
      'synchronize configuration',
    ]);
    $this->drupalLogin($this->adminUser);

    // Export initial config.
    $configsToExport = [
      'system.site',
      'system.theme',
    ];
    foreach ($configsToExport as $configName) {
      Utils::exportConfig($configName);
    }

    // Update site configuration.
    $configFactory = \Drupal::service('config.factory');
    $config = $configFactory->getEditable('system.site');
    $config->set('name', 'Test Site')->save();
  }

  /**
   * Tests GitHub repository configuration and pull request creation.
   *
   * @return void
   *   Nothing to return.
   */
  public function testConfigPrGithub(): void {
    $this->runRepositoryTests('github');
  }

  /**
   * Tests GitLab repository configuration and pull request creation.
   *
   * @return void
   *   Nothing to return.
   */
  public function testConfigPrGitlab(): void {
    $this->runRepositoryTests('gitlab');
  }

  /**
   * Tests the config pull request workflow.
   *
   * @param string $type
   *   The repository type ('github' or 'gitlab').
   *
   * @return void
   *   Nothing to return.
   */
  protected function runRepositoryTests(string $type): void {
    $baseUrl = "https://{$type}.com";
    $repoUrl = "{$baseUrl}/{$this->repoOwner}/{$this->repoName}.git";

    // Configure access token in user profile.
    $this->drupalGet("user/{$this->adminUser->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);

    $token = getenv(strtoupper($type) . '_TOKEN');
    $edit = ['field_config_pr_auth_token[0][value]' => $token];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved');

    // Configure repository settings.
    $configPath = 'admin/config/development/configuration';
    $this->drupalGet("{$configPath}/pull_request");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains('Repository configuration is missing');
    $this->clickLink('configuration page');

    $edit = [
      'repo_controller' => "config_pr_{$type}.repo_controller.{$type}",
      'repo_url' => $repoUrl,
      'repo_owner' => $this->repoOwner,
      'repo_name' => $this->repoName,
      'message_create' => 'New config @config_name.yml',
      'message_delete' => 'Del config @config_name.yml',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()
      ->pageTextContains('The configuration options have been saved');

    // Verify configuration.
    $controller = "config_pr_{$type}.repo_controller.{$type}";
    $this->assertSession()->fieldValueEquals('repo_controller', $controller);
    $this->assertSession()->fieldValueEquals('repo_url', $repoUrl);
    $this->assertSession()->fieldValueEquals('repo_owner', $this->repoOwner);
    $this->assertSession()->fieldValueEquals('repo_name', $this->repoName);

    // Navigate to pull request form.
    $this->clickLink('Pull Requests page');
    $this->assertSession()->pageTextContains('1 changed');
    $this->assertSession()->linkExists('View differences');
    $this->assertSession()->optionExists('source_branch', 'master');

    // Select and submit configuration.
    $page = $this->getSession()->getPage();
    $page->find('css', '[name="select-system_site"]')->check();
    $this->assertSession()->checkboxChecked('select-system_site');

    $edit = [
      'pr_title' => 'Test PR',
      'pr_description' => 'Automated test PR',
    ];
    $this->submitForm($edit, 'Create Pull Request');
  }

}
