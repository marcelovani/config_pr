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
  public function getControllerName(): string;

  /**
   * Get the controller id.
   *
   * This matches the id found in services.yml.
   *
   * @return string
   *   The controller id.
   */
  public function getControllerId(): string;

  /**
   * Setter for repo owner.
   *
   * @param string $repo_owner
   *   The repo owner.
   */
  public function setRepoOwner($repo_owner): void;

  /**
   * Getter for repo owner.
   * 
   * @return string
   *   The repo owner.
   */
  public function getRepoOwner(): string;

  /**
   * Setter for repo name.
   *
   * @param string $repo_name
   *   The repo name.
   */
  public function setRepoName($repo_name): void;

  /**
   * Getter for repo name.
   * 
   * @return string
   *   The repo name.
   */
  public function getRepoName(): string;

  /**
   * Getter for repo Id.
   * 
   * @return string
   *   The repo Id.
   */
  public function getRepoId(): string;

  /**
   * Setter for committer.
   *
   * @param array $committer
   *   An array containing user and email.
   */
  public function setCommitter($committer): void;

  /**
   * Getter for committer.
   * 
   * @param string $item
   *   The key for the array.
   *
   * @return string
   *   The value.
   */
  public function getCommitter($item): string;

  /**
   * Setter for access token.
   *
   * @param string $authToken
   *   The Authentication token.
   */
  public function setAuthToken($authToken): void;

  /**
   * Getter for access token.
   * 
   * @return string
   *   The access token.
   */
  public function getAuthToken(): string;

  /**
   * Gets the client instance.
   * 
   * @return object
   *   The client object.
   */
  public function getClient(): object;

  /**
   * Setter for Sha.
   * 
   * @param string
   *   The Sha.
   */
  public function setSha($sha): void;

  /**
   * Get the Sha.
   * 
   * @return string
   *   The Sha.
   */
  public function getSha(): string;

  /**
   * Get the default branch.
   * 
   * @return string
   *   The branch name.
   */
  public function getDefaultBranch(): string;

  /**
   * Creates the authentication using the token.
   * 
   * @return boolean
   *   True if authentication worked or False.
   */
  public function authenticate(): bool;

  /**
   * Finds the project details for a given repo.
   * 
   * @return boolean
   *   True if were able to fetch details, or False.
   */
  public function getProjectDetails(): bool;

  /**
   * Get the branches.
   * 
   * @return array
   *   The list of branches.
   */
  public function getBranches(): array;

  /**
   * Creates branches.
   *
   * @param string $name
   *   The branch name.
   * 
   * @return bool
   *   TRUE if successfull otherwise FALSE.
   */
  public function createBranch($name): bool;

  /**
   * Checks if a branch exists in the repo.
   *
   * @param string $branchName
   *   The branch name.
   *
   * @return bool
   *   TRUE if exists, FALSE if it doens't exist
   */
  public function branchExists($branchName): bool;

  /**
   * Returns a list of open pull requests.
   * 
   * @return array
   *   The list of Pull requests.
   */
  public function getOpenPrs(): array;

  /**
   * Creates pull requests.
   * 
   * @param string $base
   *   The base.
   * @param string $branch
   *   The branch.
   * @param string $title
   *   The title.
   * @param string $body
   *   The body.
   * 
   * @return array|bool
   *   Array if sucessfull or FALSE
   */
  public function createPr($base, $branch, $title, $body): array|bool;

  /**
   * Get the SHA of the file.
   *
   * @param string $path
   *   The absolute path and file repo name.
   * 
   * @return string
   *   The file Sha.
   */
  public function getFileSha($path): string;

  /**
   * Check if file exists in the repo.
   *
   * @param string $path
   *   The absolute path.
   * 
   * @return bool
   *   TRUE if file exists or FALSE.
   */
  public function fileExists($path): bool;

  /**
   * Creates files.
   * 
   * @param string $base
   *   The base.
   * @param string $content
   *   The content.
   * @param string $commitMessage
   *   The commit message.
   * @param string $branchName
   *   The branch name.
   * 
   * @return array|bool
   *   Array if sucessfull or FALSE
   */
  public function createFile($path, $content, $commitMessage, $branchName): array|bool;

  /**
   * Creates files.
   * 
   * @param string $base
   *   The base.
   * @param string $content
   *   The content.
   * @param string $commitMessage
   *   The commit message.
   * @param string $branchName
   *   The branch name.
   * 
   * @return array|bool
   *   Array if sucessfull or FALSE
   */
  public function updateFile($path, $content, $commitMessage, $branchName): array|bool;

  /**
   * Deletes files.
   * 
   * @param string $path
   *   The file path.
   * @param string $commitMessage
   *   The commit message.
   * @param string $branchName
   *   The branch name.
   * 
   * @return bool
   *   TRUE if sucessfull or FALSE
   */
  public function deleteFile($path, $commitMessage, $branchName): bool;

}
