<?php

namespace Drupal\Tests\config_pr\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Configuration Pull Request GitLab module functionality.
 *
 * @group config_pr
 */
class ConfigPrGitlabTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_pr',
    'config_pr_gitlab',
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
   * Tests the GitLab configuration workflow.
   */
  public function testGitlabConfiguration() {
    // Configure GitLab token in user profile.
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    
    $edit = [
      'gitlab_access_token' => 'XYZ',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved');

    // Configure the GitLab repository settings.
    $this->drupalGet('admin/config/development/configuration/pull_request');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'provider' => 'gitlab',
      'repository_url' => 'http://github.com/foo/bar.git',
      'repository_owner' => 'foo',
      'repository_name' => 'bar',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved');

    // Verify the configuration is saved correctly.
    $this->drupalGet('admin/config/development/configuration/pull_request');
    $this->assertSession()->fieldValueEquals('repository_url', 'http://github.com/foo/bar.git');
    $this->assertSession()->fieldValueEquals('repository_owner', 'foo');
    $this->assertSession()->fieldValueEquals('repository_name', 'bar');
  }

}
