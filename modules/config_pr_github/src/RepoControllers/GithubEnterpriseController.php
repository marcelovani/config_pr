<?php

namespace Drupal\config_pr_github\RepoControllers;

use Github\HttpClient\Builder;
use Http\Discovery\Psr18ClientDiscovery;

/**
 * Class to define the Github Enterprise controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GithubEnterpriseController extends GithubController {

  /**
   * Holds the controller name.
   *
   * @var string
   *   The controller name.
   */
  protected $controllerName = 'Github Enterprise';

  /**
   * Holds the controller Id.
   *
   * @var string
   *   The controller id.
   */
  protected $controllerId = 'config_pr_github.repo_controller.github_enterprise';

  /**
   * {@inheritdoc}
   */
  public function authenticate(): bool {
    $repo_url = \Drupal::service('config.factory')->get('config_pr.settings')->get('repo.repo_url');

    // Discover PSR-18 client
    $httpClient = Psr18ClientDiscovery::find();

    $builder = new Builder($httpClient);
    $this->client = new \Github\Client($builder, NULL, $repo_url);

    $this->client->authenticate(
      $this->getAuthToken(), 
      NULL, 
      \Github\AuthMethod::ACCESS_TOKEN
    );

    // Check if we can get project details.
    if ($this->getProjectDetails()) {
      return TRUE;
    }

    return FALSE;
  }

}
