<?php

namespace Drupal\config_pr_gitlab\RepoControllers;

use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\config_pr\RepoControllerInterface;

/**
 * Class to define the Gitlab controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GitLabController implements RepoControllerInterface {

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
  protected $name = 'GitLab';

  /**
   * Holds the controller Id.
   *
   * @var string
   *   The controller id.
   */
  protected $id = 'config_pr_gitlab.repo_controller.gitlab';

  /**
   * Holds the repo user.
   *
   * @var string
   *   The repo user.
   */
  protected $repoUser;

  /**
   * Holds the repo name.
   *
   * @var string
   *   The repo name.
   */
  protected $repoName;

  /**
   * Holds the default branch name.
   *
   * @var string
   *   The branch name.
   */
  protected $defaultBranch;

  /**
   * Holds the project id.
   *
   * @var string
   *   The project id.
   */
  protected $projectId;

  /**
   * Holds the authentication token.
   *
   * @var string
   *   The Authentication token.
   */
  protected $authToken;

  /**
   * Holds client instance.
   *
   * @var string
   *   The client instance.
   */
  protected $client;

  /**
   * Holds committer infor.
   *
   * @var array
   *   The committer username and email.
   */
  protected $committer = [];

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
    $this->client = new \Gitlab\Client();
    $this->client->authenticate($this->getAuthToken(), \Gitlab\Client::AUTH_HTTP_TOKEN);

    // Check if we can get project details.
    if ($this->getProjectDetails()) {
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
   * Get project id.
   *
   * @return string
   *   The project id.
   */
  public function getProjectId() {
    return $this->projectId;
  }

  /**
   * Finds the project details for a given repo.
   * 
   * @return array|boolean
   *   The project details or False.
   */
  public function getProjectDetails() {
    $params = [
      'scope' => 'projects',
      'search' => $this->repoUser . '/' . $this->repoName,
    ];

    $response = $this->getClient()->search()->all($params);
    if (is_array($response)) {
      foreach ($response as $item) {
        if ($item['path_with_namespace'] === $params['search']) {
          $this->projectId = $item['id'];
          $this->defaultBranch = $item['default_branch'];
          return [
            'projet_id' => $this->projectId,
            'default_branch' => $this->defaultBranch,
          ];
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenPrs() {
    $result = [];
    $client = $this->getClient();

    $openPullRequests = $client->mergeRequests()
      ->all($this->getProjectId(), ['state' => 'opened']);

    foreach ($openPullRequests as $item) {
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

      $result[] = [
        'number' => '#' . $item['iid'],
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
    return $this->defaultBranch;
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
      return $result['commit']['id'];
    }
  }

  /**
   * List branches.
   *
   * @return array
   *   The list of branches.
   */
  private function getBranches() {
    return $this->getClient()->repositories()->branches($this->getProjectId());
  }

  /**
   * Checks if a branch exists.
   *
   * @param string $branchName
   *   The branch name.
   */
  public function branchExists($branchName) {
    if ($this->findBranch($branchName)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks if a branch exists.
   *
   * @param string $branchName
   *   The branch name.
   * 
   * @return string|boolean
   *   The branch name or FALSE.
   */
  private function findBranch($branchName) {
    $branches = $this->getBranches();
    if (!empty($branches)) {
      foreach ($branches as $item) {
        if ($item['name'] == $branchName) {
          return $item;
        }
      }
    }

    return FALSE;
  }

  /**
   * Creates a new branch from the default branch.
   *
   * @param string $branchName
   *   The branch name.
   *
   * @return string|boolean
   *   The branch name or False.
   */
  public function createBranch($branchName) {
    $defaultBranch = $this->getDefaultBranch();

    if ($sha = $this->getSha($defaultBranch)) {
      if ($this->branchExists($branchName)) {
        return FALSE;
      }

      try {
        $branch = $this->getClient()
          ->repositories()
          ->createBranch($this->getProjectId(), $branchName, $sha);
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
        ->mergeRequests()
        ->create($this->getProjectId(),
                  $this->getDefaultBranch(),
                  $branch,
                  $title,
                  [ null, null, $body]
                );

      $pullRequest['number'] = $pullRequest['iid'];
      $pullRequest['url'] = $pullRequest['web_url'];

      return $pullRequest;
    }
    catch (GitLab\Exception\ValidationFailedException $e) {
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
          ->repositories()
          ->contents()
          ->show($this->getRepoUser(), $this->getRepoName(), $path, $sha);

        return $result['sha'];
      }
    }
    catch (GitLab\Exception\RuntimeException $e) {
      $this->messenger()->addWarning($this->t('Could not find any previous commits on repo. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return '';
    }
    catch (\Exception $e) {
      $this->messenger()->addWarning($this->t('Could not find any previous commits on repo. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return '';
    }
  }

  /**
   * Prepares the params array.
   *
   * @param string $path
   *   The file path.
   * @param string $content
   *   The file contents.
   * @param string $commitMessage
   *   The commit message.
   * @param string $branchName
   *   The branch name.
   *
   * @return array
   *   The params array.
   */
  private function prepareFileParams($path, $content, $commitMessage, $branchName) {
    return [
      'file_path' => $path,
      'branch' => $branchName,
      'encoding' => 'base64',
      'author_email' => $this->getCommitter()['email'],
      'author_name' => $this->getCommitter()['name'],
      'content' => base64_encode($content),
      'commit_message' => $commitMessage,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName) {
    // Create the file.
    try {
      // var_dump($this->getProjectId(),
      // $this->prepareFileParams($path, $content, $commitMessage, $branchName));exit;
      return $this->getClient()
        ->repositoryFiles()
        ->createFile($this->getProjectId(),
                     $this->prepareFileParams($path, $content, $commitMessage, $branchName)
                    );
    }
    catch (GitLab\Exception\RuntimeException $e) {
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
    try {
      return $this->getClient()
        ->repositoryFiles()
        ->updateFile($this->getProjectId(),
                     $this->prepareFileParams($path, $content, $commitMessage, $branchName)
                    );
    }
    catch (GitLab\Exception\RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot update file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
    catch (\Exception $e) {
      // When files do not exist, we need to try to create them.
      if ($e->getCode() === 400) {
        return $this->createFile($path, $content, $commitMessage, $branchName);
      }
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
      return $this->getClient()
        ->repositoryFiles()
        ->deleteFile($this->getProjectId(),
                     $this->prepareFileParams($path, '', $commitMessage, $branchName)
                    );
    }
    catch (GitLab\Exception\RuntimeException $e) {
      $this->messenger()->addError($this->t('Cannot delete file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Cannot delete file. Error: @msg.', [ '@msg' => $e->getMessage() ]));
      return FALSE;
    }
  }

}
