<?php

namespace Drupal\config_pr;

/**
 * Defines an interface a chained service.
 */
interface ChainRepoManagerInterface extends RepoManagerInterface {

  public function getProviders();

  /**
   * Adds another repo provider.
   *
   * @param \Drupal\config_pr\RepoManagerInterface $provider
   *   The repo provider to add.
   */
  public function addProvider(RepoManagerInterface $provider);

}
