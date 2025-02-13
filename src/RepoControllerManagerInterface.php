<?php

namespace Drupal\config_pr;

/**
 * Interface definition for ConfigPr plugins.
 *
 * @see \Drupal\config_pr\RepoControllerManagerInterface
 */
interface RepoControllerManagerInterface {

  /**
   * Adds repo controllers that were discovered.
   *
   * @param \Drupal\config_pr\RepoControllerManagerInterface $controller
   *   The Repo controller manager interface.
   */
  public function addController(RepoControllerInterface $controller);

  /**
   * Returns a list of discovered repo controllers.
   */
  public function getControllers();

  /**
   * Get repo info form local repo configuration.
   */
  public function getLocalRepoInfo();

}
