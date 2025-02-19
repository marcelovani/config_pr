<?php

namespace Drupal\config_pr\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;
use Drupal\config_pr\RepoControllerTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class to define a dummy controller.
 * 
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class DummyController implements RepoControllerInterface {
  
  use MessengerTrait;
  use RepoControllerTrait;
  use StringTranslationTrait;

  /**
   * Holds the controller name.
   *
   * @var string
   *   The controller name.
   */
  protected $controllerName = 'Dummy';

  /**
   * Holds the controller Id.
   *
   * @var string
   *   The controller id.
   */
  protected $controllerId = 'config_pr.dummy_repo_controller';

    /**
   * {@inheritdoc}
   */
  public function authenticate(): bool {}

  /**
   * @inheritDoc
   */
  public function getProjectDetails(): bool {}

  /**
   * @inheritDoc
   */
  public function getBranches(): array {}

  /**
   * @inheritDoc
   */
  public function createBranch($branch_name): bool {}
 
    /**
   * {@inheritdoc}
   */
  public function getOpenPrs(): array {}

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body): array|bool {}

  /**
   * @inheritDoc
   */
  public function getFileSha($path): string {}

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName): array|bool {}

  /**
   * {@inheritdoc}
   */
  public function fileExists($path): bool {}

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName): array|bool {}

  /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commitMessage, $branchName): bool {}

}
