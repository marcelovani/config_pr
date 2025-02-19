<?php

namespace Drupal\config_pr;

/**
 * Provides a trait for the Repo controller.
 */
trait RepoControllerTrait {

  /**
   * Holds the repo owner.
   *
   * @var string
   *   The repo owner.
   */
  protected $repoOwner;

  /**
   * Holds the repo name.
   *
   * @var string
   *   The repo name.
   */
  protected $repoName;

  /**
   * Holds the repo id.
   *
   * @var string
   *   The repo id.
   */
  protected $repoId;

  /**
   * Holds the commiter name and email.
   *
   * @var array
   *   The committer username and email
   */
  private $committer = [];

  /**
   * Holds the access token.
   *
   * @var string
   *   The access token.
   */
  protected $authToken;

  /**
   * Holds the client instance.
   *
   * @var object
   *    The client instance.
   */
  protected $client;

  /**
   * Holds the commit id for the selected branch.
   *
   * @var string
   *   The Sha.
   */
  protected $sha;

  /**
   * Holds the list of branches.
   *
   * @var array
   *   The branches keyed by their sha.
   */
  protected $branches;

  /**
   * Holds the default branch name.
   *
   * @var string
   *   The branch name.
   */
  protected $defaultBranch;

  /**
   * {@inheritdoc}
   */
  public function getControllerName(): string {
    return $this->controllerName;
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerId(): string {
    return $this->controllerId;
  }

  /**
   * {@inheritdoc}
   */
  public function setRepoOwner($repo_owner): void {
    $this->repoOwner = $repo_owner;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoOwner(): string {
    return $this->repoOwner;
  }

  /**
   * {@inheritdoc}
   */
  public function setRepoName($repo_name): void {
    $this->repoName = $repo_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setCommitter($committer): void {
    $this->committer = $committer;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoName(): string {
    return $this->repoName;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoId(): string {
    return $this->repoId;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommitter($item): string {
    return $this->committer[$item] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthToken($authToken): void {
    $this->authToken = $authToken;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthToken(): string {
    return $this->authToken;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient(): object {
    return $this->client;
  }

  /**
   * {@inheritDoc}
   */
  public function setSha($sha): void {
    $this->sha = $sha;
  }

  /**
   * {@inheritDoc}
   */
  public function getSha(): string {
    return $this->sha ?? '';
  }

  /**
   * Get the default branch.
   */
  public function getDefaultBranch(): string {
    return $this->defaultBranch;
  }

  /**
   * {@inheritDoc}
   */
  public function branchExists($branch_name): bool {
    return in_array($branch_name, $this->getBranches());
  }

}
