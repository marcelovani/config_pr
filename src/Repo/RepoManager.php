<?php

namespace Drupal\config_pr\Repo;

class RepoManager implements ChainRepoManagerInterface {

  /**
   * Holds arrays of repo providers.
   *
   * @var array
   */
  protected $providers = [];

  /**
   * {@inheritdoc}
   */
  public function addProvider(RepoManagerInterface $provider) {
    $this->providers[] = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'Repo Manager';
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'config_pr.repo_manager';
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders() {
    foreach ($this->providers as $provider) {
      $providers[$provider->getId()] = $provider->getName();
    }

    return $providers;
  }

}
