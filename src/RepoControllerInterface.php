<?php

namespace Drupal\config_pr;

/**
 * Interface definition for ConfigPr plugins.
 *
 * @see \Drupal\config_pr\ConfigPrController
 * @see plugin_api
 */
interface RepoControllerInterface {

  /**
   * Setter for username.
   *
   * @param $username
   *   The repo username
   */
  public function setUsername($username);

  /**
   * Setter for name.
   *
   * @param $name
   *   The repo name
   */
  public function setName($name);

  /**
   * Setter for committer.
   *
   * @param $committer
   *   An array containing user and email.
   */
  public function setCommitter($committer);

  /**
   * Getter for username.
   */
  public function getUsername();

  /**
   * Getter for name.
   */
  public function getName();

  /**
   * Getter for committer.
   */
  public function getCommitter();

  /**
   * Setter for token auth.
   *
   * @param $authToken
   *   The Authentication token
   */
  public function setAuthToken($authToken);

  /**
   * Returns a list of open pull requests.
   */
  public function getOpenPrs();

  /**
   * Gets the client instance.
   */
  public function getClient();

  /**
   * Get the default branch.
   */
  public function getDefaultBranch();

  /**
   * Get the Sha of branch.
   */
  public function getSha($branch);

  /**
   * Creates the authentication using the token.
   */
  public function authenticate();

  /**
   * Creates branches.
   *
   * @param $branchName
   *   The branch name.
   */
  public function createBranch($branchName);

  /**
   * Checks if a branch exists in the repo.
   *
   * @param $branchName
   *
   * @return TRUE/FALSE
   *   TRUE if exists, FALSE if it doens't exist
   */
  public function branchExists($branchName);

  /**
   * Creates pull requests.
   */
  public function createPr($base, $branch, $title, $body);

  /**
   * Creates files.
   */
  public function createFile($path, $content, $commitMessage, $branchName);
}
