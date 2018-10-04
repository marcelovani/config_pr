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

  public function getName() {
    return 'Repo Manager';
  }

  public function getId() {
    return 'config_pr.repo_manager';
  }

  public function getProviders() {
    foreach ($this->providers as $provider) {
    //var_dump($provider);exit;

      $names[$provider->getId()] = $provider->getName();
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return 'something';
  }

  /**
   * Returns the sorted array of repo providers.
   *
   * @return \Drupal\config_pr\Repo\RepoManagerInterface[]
   *   An array of repo provider objects.
   */
  protected function getSortedProviders() {
    if (!isset($this->sortedProviders)) {
      // Sort the providers according to priority.
      krsort($this->providers);
      // Merge nested providers from $this->providers into $this->sortedProviders.
      $this->sortedProviders = [];
      foreach ($this->providers as $providers) {
        $this->sortedProviders = array_merge($this->sortedProviders, $providers);
      }
    }
    return $this->sortedProviders;
  }

}
