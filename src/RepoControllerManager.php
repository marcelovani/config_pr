<?php

namespace Drupal\config_pr;

/**
 * Class RepoControllerManager.
 *
 * Works as a controller collector to discover services that are tagged
 * with config_pr.repo_controller.
 *
 * @package Drupal\config_pr
 */
class RepoControllerManager implements RepoControllerManagerInterface {

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
  public function getControllers() {
    foreach ($this->controllers as $controller) {
      $controllers[$controller->getControllerId()] = $controller->getControllerName();
    }

    return $controllers;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalRepoInfo() {
    $git_config = dirname(DRUPAL_ROOT) . '/.git/config';
    if (file_exists($git_config)) {
      $config = parse_ini_file($git_config);
      preg_match("|git.*:(\w+)\/(\w+)|", $config['url'], $matches);
      if (!empty($matches)) {
        return [
          'repo_owner' => $matches[1],
          'repo_name' => $matches[2],
        ];
      }
    }
  }

}
