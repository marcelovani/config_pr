<?php

namespace Drupal\config_pr\Repo;

/**
 * Defines an interface a chained service that builds the repo.
 */
interface ChainRepoManagerInterface extends RepoManagerInterface {

  public function getProviders();

  /**
   * Adds another repo provider.
   *
   * @param \Drupal\config_pr\Repo\RepoManagerInterface $provider
   *   The repo provider to add.
   */
  public function addProvider(RepoManagerInterface $provider);

}
