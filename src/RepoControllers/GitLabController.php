<?php

namespace Drupal\config_pr\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;

/**
 * Class to define the Gitlab controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GitlabController implements RepoControllerInterface {

  /**
   * Holds the controller name.
   *
   * @var string $name.
   */
  protected $name = 'GitLab';

  /**
   * Holds the controller Id.
   *
   * @var string $id.
   */
  protected $id = 'config_pr.repo_controller.gitlab';

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

  public function getOpenPrs() {
    \Drupal::messenger()->addError('Gitlab controller is not ready to be used yet!');
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalRepoInfo() {}

  /**
   * {@inheritdoc}
   */
  public function setCommitter($committer) {}

  /**
   * {@inheritdoc}
   */
  public function getRepoName() {}

  /**
   * {@inheritdoc}
   */
  public function branchExists($branchName) {}

  /**
   * {@inheritdoc}
   */
  public function getSha($branch) {}

  /**
   * {@inheritdoc}
   */
  public function setRepoName($repo_name) {}

  /**
   * {@inheritdoc}
   */
  public function getCommitter() {}

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName) {}

  /**
   * {@inheritdoc}
   */
  public function createBranch($branchName) {}

  /**
   * {@inheritdoc}
   */
  public function authenticate() {}

  /**
   * {@inheritdoc}
   */
  public function getDefaultBranch() {}

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body) {}

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName) {}

  /**
   * {@inheritdoc}
   */
  public function getRepoUser() {}

  /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commitMessage, $branchName) {}

  /**
   * {@inheritdoc}
   */
  public function setAuthToken($authToken) {}

  /**
   * {@inheritdoc}
   */
  public function setRepoUser($repo_user) {}

  /**
   * {@inheritdoc}
   */
  public function getClient() {}

}
