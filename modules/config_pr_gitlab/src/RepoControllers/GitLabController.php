<?php

namespace Drupal\config_pr_gitlab\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;
use Drupal\config_pr\RepoControllerTrait;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class to define the Gitlab controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GitLabController implements RepoControllerInterface {

  use MessengerTrait;
  use RepoControllerTrait;
  use StringTranslationTrait;

  /**
   * Holds the controller name.
   *
   * @var string
   *   The controller name.
   */
  protected $controllerName = 'GitLab';

  /**
   * Holds the controller Id.
   *
   * @var string
   *   The controller id.
   */
  protected $controllerId = 'config_pr_gitlab.repo_controller.gitlab';

  /**
   * Holds the Gitlab client instance.
   *
   * @var \Gitlab\Client
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
    $this->client = new \Gitlab\Client();
    $this->client->authenticate(
      $this->getAuthToken(), 
      \Gitlab\Client::AUTH_HTTP_TOKEN
    );

    // Check if we can get project details.
    if ($this->getProjectDetails()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getProjectDetails(): bool {
    $params = [
      'scope' => 'projects',
      'search' => $this->getRepoOwner() . '/' . $this->getRepoName(),
    ];

    $details = $this->getClient()->search()->all($params);

    foreach ($details ?? [] as $item) {
      if ($item['path_with_namespace'] === $params['search']) {
        $this->repoId = $item['id'];
        $this->defaultBranch = $item['default_branch'];
        $this->getBranches();
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getBranches(): array {
    if (!empty($this->branches)) {
      return $this->branches;
    }

    $result = $this->getClient()
      ->repositories()
      ->branches($this->getRepoId()) ?? [];

    $this->branches = [];
    foreach ($result as $item) {
      $revision = $item['commit']['id'];
      $this->branches[$revision] = $item['name'];
      // Set Sha.
      if ($item['name'] === $this->getDefaultBranch()) {
        $this->setSha($revision);
      }
    }

    // If the default branch is not in the list, add it to the list.
    if (empty($this->getSha())) {
      $result = $this->getClient()
        ->repositories()
        ->branch($this->getRepoId(), $this->getDefaultBranch()) ?? [];

      $revision = $result['commit']['id'];
      $this->branches[$revision] = $result['name'];
      $this->setSha($revision);
    }

    return $this->branches;
  }

  /**
   * @inheritDoc
   */
  public function createBranch($branch_name): bool {
    try {
      $this
        ->getClient()
        ->repositories()
        ->createBranch(
          $this->getRepoId(),
          $branch_name,
          $this->getSha()
        );
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot create branch. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenPrs(): array {
    $result = $this->getClient()
      ->mergeRequests()
      ->all($this->getRepoId(), ['state' => 'opened']);

    $prs = [];
    foreach ($result ?? [] as $item) {
      $link = Link::fromTextAndUrl(
        'Open',
        Url::fromUri(
          $item['web_url'],
          [
            'attributes' => [
              'target' => '_blank',
            ]
          ],
        )
      );

      $prs[] = [
        'number' => '#' . $item['iid'],
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
        ->mergeRequests()
        ->create($this->getRepoId(),
                  $branch,
                  $base,
                  $title,
                  [ null, null, $body]
                );
      $pr['number'] = $pr['iid'];
      $pr['url'] = $pr['web_url'];

      return $pr;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot create pull request. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

  /**
   * @inheritDoc
   */
  public function getFileSha($path): string {
    try {
      // Get file SHA.
      $result = $this
        ->getClient()
        ->repositoryFiles()
        ->getFile(
          $this->getRepoId(),
          $path,
          $this->getSha()
        );

      return $result['ref'] ?? '';
    }
    catch (\Exception $e) {
      return '';
    }

    return '';
  }

  /**
   * Prepares the params array.
   *
   * @param string $path
   *   The file path.
   * @param string $content
   *   The file contents.
   * @param string $commit_message
   *   The commit message.
   * @param string $branch_name
   *   The branch name.
   *
   * @return array
   *   The params array.
   */
  private function prepareFileParams($path, $content, $commit_message, $branch_name) {
    return [
      'file_path' => $path,
      'branch' => $branch_name,
      'encoding' => 'base64',
      'author_email' => $this->getCommitter('email'),
      'author_name' => $this->getCommitter('name'),
      'content' => base64_encode($content),
      'commit_message' => $commit_message,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commit_message, $branch_name): array|bool {
    // Create the file.
    try {
      return $this
        ->getClient()
        ->repositoryFiles()
        ->createFile(
          $this->getRepoId(),
          $this->prepareFileParams(
            $path,
            $content,
            $commit_message,
            $branch_name
          )
        );
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot create file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fileExists($path): bool {
    return (!empty($this->getFileSha($path)));
  }

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branch_name): array|bool {
    // Check if file exists on repo.
    if (!$this->fileExists($path)) {
      return $this->createFile($path, $content, $commitMessage, $branch_name);
    }

    try {
      return $this
        ->getClient()
        ->repositoryFiles()
        ->updateFile(
          $this->getRepoId(),
          $this->prepareFileParams(
            $path,
            $content,
            $commitMessage,
            $branch_name
          )
        );
    }
    catch (\Exception $e) {
      // When files do not exist, we need to try to create them.
      if ($e->getCode() === 400) {
        return $this->createFile($path, $content, $commitMessage, $branch_name);
      }
      $this->messenger()->addError($this->t('Cannot update file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commit_message, $branch_name): bool {
    // Delete the file.
    try {
      return $this
      ->getClient()
        ->repositoryFiles()
        ->deleteFile(
          $this->getRepoId(),
          $this->prepareFileParams(
            $path,
            '',
            $commit_message,
            $branch_name
          )
        );
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot delete file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

}
