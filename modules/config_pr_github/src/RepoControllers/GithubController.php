<?php

namespace Drupal\config_pr_github\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;
use Drupal\config_pr\RepoControllerTrait;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Github\Exception\RuntimeException;
use Github\Exception\ValidationFailedException;
use Github\Api\GitData\References;

/**
 * Class to define the Github controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GithubController implements RepoControllerInterface {

  use MessengerTrait;
  use RepoControllerTrait;
  use StringTranslationTrait;

  /**
   * Holds the controller name.
   *
   * @var string
   *   The controller name.
   */
  protected $controllerName = 'Github';

  /**
   * Holds the controller Id.
   *
   * @var string
   *   The controller id.
   */
  protected $controllerId = 'config_pr_github.repo_controller.github';

  /**
   * Holds the Github client instance.
   *
   * @var \Github\Client
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
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(): bool {
    if (empty($this->client)) {
      $this->client = new \Github\Client();
    }
    $this->client->authenticate(
      $this->getAuthToken(),
      NULL,
      \Github\AuthMethod::ACCESS_TOKEN
    );

    // Check if we can get project details.
    if ($this->getProjectDetails()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getProjectDetails(): bool {
    $details = $this->getClient()
      ->api('repo')
      ->show($this->getRepoOwner(), $this->repoName);

    if (empty($details)) {
      return FALSE;
    }

    $this->repoId = $details['id'];
    $this->defaultBranch = $details['default_branch'];
    $this->getBranches();

    return TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function getBranches(): array {
    if (!empty($this->branches)) {
      return $this->branches;
    }

    $result = $this->getClient()
      ->api('repo')
      ->branches(
        $this->getRepoOwner(),
        $this->getRepoName()
      );

    $this->branches = [];
    foreach ($result as $item) {
      $revision = $item['commit']['sha'];
      $this->branches[$revision] = $item['name'];
      // Set Sha.
      if ($item['name'] === $this->getDefaultBranch()) {
        $this->setSha($revision);
      }
    }

    // If the default branch is not in the list, add it to the list.
    if (empty($this->getSha())) {
      $result = $this->getClient()
        ->api('repo')
        ->branches(
          $this->getRepoOwner(),
          $this->getRepoName(),
          $this->getDefaultBranch()
        );

      $revision = $result['commit']['sha'];
      $this->branches[$revision] = $result['name'];
      $this->setSha($revision);
    }

    return $this->branches;
  }

  /**
   * {@inheritDoc}
   */
  public function createBranch($branch_name): bool {
    $references = new References($this->getClient());

    try {
      $references->create(
        $this->getRepoOwner(),
        $this->getRepoName(),
        [
          'ref' => 'refs/heads/' . $branch_name,
          'sha' => $this->getSha(),
        ]
      );
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot create branch. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenPrs(): array {
    $result = $this->getClient()
      ->api('pull_request')
      ->all($this->repoOwner,
        $this->repoName,
        ['state' => 'open']
      );

    $prs = [];
    foreach ($result ?? [] as $item) {
      $link = Link::fromTextAndUrl(
        'Open',
        Url::fromUri(
          $item['html_url'],
          [
            'attributes' => [
              'target' => '_blank',
            ],
          ],
        )
      );

      $prs[] = [
        'number' => '#' . $item['number'],
        'title' => $item['title'],
        'link' => $link,
      ];
    }

    return $prs;
  }

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body): array|bool {
    try {
      $pr = $this->getClient()
        ->api('pull_request')
        ->create($this->repoOwner, $this->repoName, [
          'base' => $base,
          'head' => $branch,
          'title' => $title,
          'body' => $body,
          'ref' => "refs/head/$branch",
          'sha' => $this->getSha(),
        ]);

      $pr['url'] = $pr['html_url'];

      return $pr;
    }
    catch (ValidationFailedException $e) {
      $this->messenger()->addError($this->t('Cannot create pull request. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot create pull request. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot create pull request. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getFileSha($path): string {
    try {
      // Get file SHA.
      $result = $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->show(
          $this->getRepoOwner(),
          $this->getRepoName(),
          $path,
          $this->getSha()
        );

      return $result['sha'] ?? '';
    }
    catch (RuntimeException $e) {
      return '';
    }
    catch (\Exception $e) {
      return '';
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName): array|bool {
    // Create the file.
    try {
      return $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->create(
          $this->getRepoOwner(),
          $this->getRepoName(),
          $path,
          $content,
          $commitMessage,
          $branchName,
          [
            'name' => $this->getCommitter('name'),
            'email' => $this->getCommitter('email'),
          ]
        );
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot create file. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot create file. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fileExists($path): bool {
    try {
      return $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->exists(
          $this->getRepoOwner(),
          $this->getRepoName(),
          $path,
          $this->getSha()
        );
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot check file. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot check file. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName): array|bool {
    // Check if file exists on repo.
    if (!($this->fileExists($path))) {
      return $this->createFile($path, $content, $commitMessage, $branchName);
    }

    // Update the file.
    try {
      return $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->update(
          $this->getRepoOwner(),
          $this->getRepoName(),
          $path,
          $content,
          $commitMessage,
          $this->getFileSha($path),
          $branchName,
          [
            'name' => $this->getCommitter('name'),
            'email' => $this->getCommitter('email'),
          ]
        );
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot update file. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot update file. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commitMessage, $branchName): bool {
    // Delete the file.
    try {
      return $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->rm($this->getRepoOwner(),
          $this->getRepoName(),
          $path,
          $commitMessage,
          $this->getFileSha($path),
          $branchName,
          [
            'name' => $this->getCommitter('name'),
            'email' => $this->getCommitter('email'),
          ]
      );
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot delete file. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot delete file. Error: @msg.', ['@msg' => $e->getMessage()]));
      return FALSE;
    }
  }

}
