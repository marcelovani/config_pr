<?php

namespace Drupal\config_pr_gitlab\RepoControllers;

use Gitlab\HttpClient\Builder;
use Http\Discovery\Psr18ClientDiscovery;

/**
 * Class to define the Gitlab self managed controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GitlabSelfManagedController extends GitlabController {

  /**
   * Holds the controller name.
   *
   * @var string
   *   The controller name.
   */
  protected $controllerName = 'Gitlab Self Managed';

  /**
   * Holds the controller Id.
   *
   * @var string
   *   The controller id.
   */
  protected $controllerId = 'config_pr_gitlab.repo_controller.gitlab_self_managed';

  /**
   * {@inheritdoc}
   */
  public function authenticate(): bool {
    $repo_url = \Drupal::service('config.factory')->get('config_pr.settings')->get('repo.repo_url');

    // Discover PSR-18 client.
    $httpClient = Psr18ClientDiscovery::find();

    $builder = new Builder($httpClient);
    $this->client = new \Gitlab\Client($builder);
    $this->client->setUrl($repo_url);

    $this->client->authenticate(
      $this->getAuthToken(),
      \Gitlab\Client::AUTH_HTTP_TOKEN
    );

    // Check if we can get project details.
    if ($this->getProjectDetails()) {
      return TRUE;
    }

    return FALSE;
  }

}
