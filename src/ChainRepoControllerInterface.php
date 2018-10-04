<?php

namespace Drupal\config_pr;

/**
 * Defines an interface a chained service.
 */
interface ChainRepoControllerInterface extends RepoControllerInterface {

  public function getControllers();

  /**
   * Adds another repo controller.
   *
   * @param \Drupal\config_pr\RepoControllerInterface $controller
   *   The repo controller to add.
   */
  public function addController(RepoControllerInterface $controller);

}
