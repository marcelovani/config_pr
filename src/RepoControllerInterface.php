<?php

namespace Drupal\config_pr;

/**
 * Interface definition for ConfigPr plugins.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
interface RepoControllerInterface {

  /**
   * Get the controller name.
   *
   * @return string
   *   Controller name
   */
  public function getName();

  /**
   * Get the service id.
   *
   * This matches the id found in services.yml.
   *
   * @return string
   *   The id
   */
  public function getId();

  /**
   * Setter for repo user.
   *
   * @param string $repo_user
   *   The repo user.
   */
  public function setRepoUser($repo_user);

  /**
   * Setter for repo name.
   *
   * @param string $repo_name
   *   The repo name.
   */
  public function setRepoName($repo_name);

  /**
   * Setter for committer.
   *
   * @param array $committer
   *   An array containing user and email.
   */
  public function setCommitter($committer);

  /**
   * Getter for repo user.
   */
  public function getRepoUser();

  /**
   * Getter for repo name.
   */
  public function getRepoName();

  /**
   * Getter for committer.
   */
  public function getCommitter();

  /**
   * Setter for access token.
   *
   * @param string $authToken
   *   The Authentication token.
   */
  public function setAuthToken($authToken);

  /**
   * Getter for access token.
   */
  public function getAuthToken();

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
   * 
   * @return boolean
   *   True if authentication worked or False.
   */
  public function authenticate();

  /**
   * Creates branches.
   *
   * @param string $branchName
   *   The branch name.
   */
  public function createBranch($branchName);

  /**
   * Checks if a branch exists in the repo.
   *
   * @param string $branchName
   *   The branch name.
   *
   * @return bool
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

  /**
   * Creates files.
   */
  public function updateFile($path, $content, $commitMessage, $branchName);

  /**
   * Deletes files.
   */
  public function deleteFile($path, $commitMessage, $branchName);

}
