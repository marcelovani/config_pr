<?php

namespace Drupal\config_pr\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;
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

  use StringTranslationTrait;
  use MessengerTrait;

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
   * Holds the controller name.
   *
   * @var string
   *   The controller name.
   */
  protected $name = 'Github';

  /**
   * Holds the controller Id.
   *
   * @var string
   *   The controller id.
   */
  protected $id = 'config_pr.repo_controller.github';

  /**
   * Holds the repo user.
   *
   * @var string
   *   The repo user.
   */
  private $repoUser;

  /**
   * Holds the repo name.
   *
   * @var string
   *   The repo name.
   */
  private $repoName;

  /**
   * Holds the auth token.
   *
   * @var string
   *   The Authentication token.
   */
  private $authToken;

  /**
   * Holds the client hame.
   *
   * @var string
   *    The client instance
   */
  protected $client;

  /**
   * Holds the commiter name.
   *
   * @var string
   *   The committer username and email
   */
  private $committer = [];

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setRepoUser($repo_user) {
    $this->repoUser = $repo_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setRepoName($repo_name) {
    $this->repoName = $repo_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setCommitter($committer) {
    $this->committer = $committer;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoUser() {
    return $this->repoUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoName() {
    return $this->repoName;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommitter() {
    return $this->committer;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthToken($authToken) {
    $this->authToken = $authToken;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthToken() {
    return $this->authToken;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    $this->client = new \Github\Client();
    $this->client->authenticate($this->getAuthToken(), NULL, \Github\AuthMethod::ACCESS_TOKEN);

    // Check if authentication worked.
    if (!empty($this->getDefaultBranch())) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenPrs() {
    $result = [];
    $openPullRequests = $this->getClient()
      ->api('pull_request')
      ->all($this->repoUser, $this->repoName, ['state' => 'open']);

    foreach ($openPullRequests as $item) {
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

      $result[] = [
        'number' => '#' . $item['number'],
        'title' => $item['title'],
        'link' => $link,
      ];
    }

    return $result;
  }

  /**
   * Get the default branch.
   */
  public function getDefaultBranch() {
    $repoApi = new GithubApi($this->getClient());
    $path = '/repos/' . rawurlencode($this->repoUser) . '/' . rawurlencode($this->repoName);
    $response = $repoApi->get($path);

    return $response['default_branch'];
  }

  /**
   * Get the Sha of the branch.
   *
   * @param string $branch
   *   The branch.
   *
   * @return string
   *   The Sha.
   */
  public function getSha($branch) {
    if ($result = $this->findBranch($branch)) {
      return $result['object']['sha'];
    }
  }

    /**
     * List branches.
     *
     * @param \Github\Api\GitData\References $references
     *   The Git references.
     *
     * @return array
     *   The list of branches.
     */
  private function listBranches(References $references) {
    $branches = $references->branches($this->repoUser, $this->repoName);

    return $branches;
  }

  /**
   * Checks if a branch exists.
   *
   * @param string $branchName
   *   The branch.
   */
  public function branchExists($branchName) {
    if ($this->findBranch($branchName)) {
      return TRUE;
    }
  }

  /**
   * Checks if a branch exists.
   *
   * @param string $branchName
   *   The branch name.
   */
  private function findBranch($branchName) {
    $references = new References($this->getClient());
    $branches = $this->listBranches($references);
    foreach ($branches as $item) {
      if ($item['ref'] == 'refs/heads/' . $branchName) {
        return $item;
      }
    }
  }

  /**
   * Creates a new branch from the default branch.
   *
   * @param string $branchName
   *   The branch name.
   *
   * @return string|bool
   *   The branch name or False.
   */
  public function createBranch($branchName) {
    $references = new References($this->getClient());
    $defaultBranch = $this->getDefaultBranch();

    if ($sha = $this->getSha($defaultBranch)) {
      $params = [
        'ref' => 'refs/heads/' . $branchName,
        'sha' => $sha,
      ];

      if ($this->branchExists($branchName)) {
        return FALSE;
      }

      try {
        $branch = $references->create($this->repoUser, $this->repoName, $params);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t('Cannot create branch. Error: @msg.', [ '@msg' => $e->getMessage() ]));
        return FALSE;
      }

      return $branch;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body) {
    try {
      $pullRequest = $this->getClient()
        ->api('pull_request')
        ->create($this->repoUser, $this->repoName, [
          'base' => $base,
          'head' => $branch,
          'title' => $title,
          'body' => $body,
          'ref' => 'refs/head/' . $branch,
          'sha' => $this->getSha($branch),
        ]);
      $pullRequest['number'] = $pullRequest['number'];
      $pullRequest['url'] = $pullRequest['html_url'];

      return $pullRequest;
    }
    catch (ValidationFailedException $e) {
      $this->messenger()->addError($this->t('Cannot create pull request. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot create pull request. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot create pull request. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

  /**
   * Get the SHA of the file.
   *
   * @param string $path
   *   The absolute path and file repo name.
   */
  private function getFileSha($path) {
    try {
      // Get SHA of default branch.
      if ($sha = $this->getSha($this->getDefaultBranch())) {
        // Get file SHA.
        $result = $this
          ->getClient()
          ->api('repo')
          ->contents()
          ->show($this->getRepoUser(), $this->getRepoName(), $path, $sha);

        return $result['sha'];
      }
    }
    catch (RuntimeException $e) {
      $this->messenger()->addWarning($this->t('Could not find any previous commits on repo. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return '';
    }
    catch (\Exception $e) {
      $this->messenger()->addWarning($this->t('Could not find any previous commits on repo. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName) {
    // Create the file.
    try {
      $result = $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->create($this->getRepoUser(), $this->getRepoName(), $path, $content, $commitMessage, $branchName, $this->getCommitter());

      return $result;
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot create file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot create file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName) {
    /* Check if the file exists. @todo Is this necessary?
    if ($client
      ->api('repo')
      ->contents()
      ->exists($this->getRepoUser(), $this->getRepoName(), $path, $reference = null)) {
    }*/

    // Update the file.
    try {
      $result = $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->update($this->getRepoUser(), $this->getRepoName(), $path, $content, $commitMessage, $this->getFileSha($path), $branchName, $this->getCommitter());

      return $result;
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot update file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot update file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commitMessage, $branchName) {
    // Delete the file.
    try {
      $result = $this
        ->getClient()
        ->api('repo')
        ->contents()
        ->rm($this->getRepoUser(), $this->getRepoName(), $path, $commitMessage, $this->getFileSha($path), $branchName, $this->getCommitter());

      return $result;
    }
    catch (RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot delete file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot delete file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

}
