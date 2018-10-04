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

  /**
   * We have to implement these because the RepoManager is implementing the same class as the Repo Providers.
   * //@todo check if there is another way of doing this.
   */
  public function getLocalRepoInfo() {}
  public function setCommitter($committer) {}
  public function getRepoName() {}
  public function branchExists($branchName) {}
  public function getSha($branch) {}
  public function setRepoName($repo_name) {}
  public function getCommitter() {}
  public function updateFile($path, $content, $commitMessage, $branchName) {}
  public function createBranch($branchName) {}
  public function authenticate() {}
  public function getOpenPrs() {}
  public function getDefaultBranch() {}
  public function createPr($base, $branch, $title, $body) {}
  public function createFile($path, $content, $commitMessage, $branchName) {}
  public function getRepoUser() {}
  public function deleteFile($path, $commitMessage, $branchName) {}
  public function setAuthToken($authToken) {}
  public function setRepoUser($repo_user) {}
  public function getClient() {}
}
