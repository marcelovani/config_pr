<?php

namespace Drupal\config_pr_gitlab\RepoControllers;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\config_pr\RepoControllerInterface;
// use GitLab;
// use GitLab\Client;

/**
 * Class to define the Gitlab controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class GitLabController implements RepoControllerInterface {

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
  public function authenticate() {
    $this->getClient()->authenticate($this->authToken, \Gitlab\Client::AUTH_HTTP_TOKEN);
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    if (!is_null($this->client)) {
      return $this->client;
    }
    $this->client = new \Gitlab\Client();
    $this->authenticate();
    $this->getProjectDetails();

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
   */
  public function getProjectDetails() {
    $params = [
      'scope' => 'projects',
      'search' => $this->repoUser . '/' . $this->repoName,
    ];

    $response = $this->getClient()->search()->all($params);

    if (is_array($response)) {
      foreach ($response as $item) {
        if ($item['path'] == $this->repoName) {
          $this->projectId = $item['id'];
          $this->defaultBranch = $item['default_branch'];
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenPrs() {
    $result = [];
    $openPullRequests = $this->getClient()
      ->mergeRequests()
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
  }

  /**
   * Checks if a branch exists.
   *
   * @param string $branchName
   *   The branch name.
   */
  private function findBranch($branchName) {
    $branches = $this->getBranches();
    foreach ($branches as $item) {
      if ($item['name'] == $branchName) {
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
        \Drupal::messenger()->addError($e->getMessage());
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
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
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
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
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
        ->repositories()
        ->createFile($this->getProjectId(), $path, base64_encode($content), $branchName, $commitMessage, 'base64', $this->getCommitter()['email'], $this->getCommitter()['name']);

      return $result;
    }
    catch (GitLab\Exception\RuntimeException $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName) {
    try {
      $params = [
        'file_path' => $path,
        'branch' => $branchName,
        'encoding' => 'base64',
        'content' => base64_encode($content),
        'commit_message' => $commitMessage,
        'author_email' => $this->getCommitter()['email'],
        'author_name' => $this->getCommitter()['name'],
      ];

      return $this->getClient()
        ->repositoryFiles()
        ->updateFile($this->getProjectId(), $params);
    }
    catch (GitLab\Exception\RuntimeException $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
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
        ->repositories()
        ->deleteFile($this->getProjectId(), $path, $branchName, $commitMessage, $this->getCommitter()['email'], $this->getCommitter()['name']);

      return $result;
    }
    catch (GitLab\Exception\RuntimeException $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
  }

}
