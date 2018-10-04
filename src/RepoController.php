<?php

namespace Drupal\config_pr;

class RepoController implements ChainRepoControllerInterface {

  /**
   * Holds arrays of repo controllers.
   *
   * @var array
   */
  protected $controllers = [];

  /**
   * {@inheritdoc}
   */
  public function addController(RepoControllerInterface $controller) {
    $this->controllers[] = $controller;
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
    return 'config_pr.repo_controller';
  }

  /**
   * {@inheritdoc}
   */
  public function getControllers() {
    foreach ($this->controllers as $controller) {
      $controllers[$controller->getId()] = $controller->getName();
    }

    return $controllers;
  }

  /**
   * We have to implement these because the RepoController is implementing the same class as the Repo Controllers.
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
