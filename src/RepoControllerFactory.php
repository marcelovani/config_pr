<?php

namespace Drupal\config_pr;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory class for repository controllers.
 */
class RepoControllerFactory {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a new RepoControllerFactory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ContainerInterface $container,
  ) {
    $this->configFactory = $config_factory;
    $this->container = $container;
  }

  /**
   * Gets the appropriate repository controller.
   *
   * @return \Drupal\config_pr\RepoControllerInterface
   *   The repository controller.
   */
  public function getController(): RepoControllerInterface {
    $controller_id = $this->configFactory->get('config_pr.settings')->get('repo.controller');

    // Default to dummy controller if no configuration exists.
    if (empty($controller_id)) {
      $controller_id = 'config_pr.dummy_repo_controller';
    }

    return $this->container->get($controller_id);
  }

}
