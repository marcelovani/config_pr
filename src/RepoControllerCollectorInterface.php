<?php

namespace Drupal\config_pr;

use Drupal\Component\Assertion\Inspector;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Interface definition for ConfigPr Repo Controllers.
 *
 * @see \Drupal\config_pr
 */
interface RepoControllerCollectorInterface extends RepoControllerInterface {

  /**
   * Todo: Describe
   *
   * @param array $tags
   *
   * @return mixed
   */
  public function invalidateTags(array $tags);

  /**
   *
   * Todo: Describe
   * @param RepoControllerCollectorInterface $repoCollector
   *
   * @return mixed
   */
  public function addRepoController(RepoControllerCollectorInterface $repoCollector);

}
