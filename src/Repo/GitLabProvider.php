<?php

namespace Drupal\config_pr\Repo;

/**
 * Class to define the term node repo provider.
 */
class GitlabProvider implements RepoManagerInterface {

  /**
   * Holds the provider name.
   *
   * @var string $name.
   */
  protected $name = 'GitLab';

  /**
   * Holds the provider Id.
   *
   * @var string $id.
   */
  protected $id = 'config_pr.repo_provider.gitlab';

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

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
