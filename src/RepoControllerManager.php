<?php

namespace Drupal\config_pr;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

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
  protected array $controllers = [];

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new RepoControllerManager.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function addController(RepoControllerInterface $controller) {
    $id = $controller->getControllerId();
    $this->controllers[$id] = $controller;
  }

  /**
   * {@inheritdoc}
   */
  public function getControllers(): array {
    $controllers = [];
    foreach ($this->controllers as $controller) {
      $id = $controller->getControllerId();
      $controllers[$id] = $controller->getControllerName();
    }
    return $controllers;
  }

  /**
   * Gets the active controller if configured.
   *
   * @return \Drupal\config_pr\RepoControllerInterface|null
   *   The active controller, or NULL if not configured.
   */
  public function getActiveController(): ?RepoControllerInterface {
    $id = $this->configFactory->get('config_pr.settings')->get('repo.controller');
    return $id ? ($this->controllers[$id] ?? NULL) : NULL;
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
