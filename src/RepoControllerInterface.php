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
   * Creates the authentication using the token.
   */
  public function authenticate();

  /**
   * Creates pull requests.
   */
  public function createPr();
}
