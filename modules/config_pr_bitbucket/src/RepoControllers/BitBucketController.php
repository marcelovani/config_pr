<?php

namespace Drupal\config_pr_bitbucket\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;
use Drupal\config_pr\RepoControllerTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class to define the BitBucket controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class BitBucketController implements RepoControllerInterface {

  use MessengerTrait;
  use RepoControllerTrait;
  use StringTranslationTrait;

  /**
   * Holds the controller name.
   *
   * @var string
   *   The controller name.
   */
  protected $controllerName = 'BitBucket';

  /**
   * Holds the controller Id.
   *
   * @var string
   *   The controller id.
   */
  protected $controllerId = 'config_pr_bitbucket.repo_controller.bitbucket';

  /**
   * Holds the Github client instance.
   *
   * @var \Bitbucket\Client
   *    The client instance.
   */
  protected $client;

  /**
   * Drupal Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Github Controller constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Drupal Messenger Service.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
    $this->messenger()->addError('BitBucket controller is not ready to be used yet!');
    $this->messenger()->addError('The library can be found here https://github.com/BitbucketAPI/Client/blob/master/README.md');
  }

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
