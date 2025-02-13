<?php

namespace Drupal\config_pr_gitlab\RepoControllers;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\config_pr\RepoControllerInterface;
use GitLab;
use GitLab\Client;

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

  // @todo: name and id should be done via Annotations.

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
   * Jolds the project id.
   *
   * @var string
   *   The id.
   */
  private $projectId;

  /**
   * Holds the authentication token.
   *
   * @var string
   *   The Authentication token.
   */
  private $authToken;

  /**
   * Holds client instance.
   *
   * @var string
   *   The client instance.
   */
  private $client;

  /**
   * Holds committer infor.
   *
   * @var array
   *   The committer username and email.
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
  public function authenticate() {
    $this->getClient()->authenticate($this->authToken, Client::AUTH_HTTP_TOKEN);
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    if (!is_null($this->client)) {
      return $this->client;
    }
    $this->client = new Client();
    $this->authenticate();
    $this->getProjectId();

    return $this->client;
  }

  /**
   * Finds the project id for a given repo name.
   */
  public function getProjectId() {
    if (isset($this->projectId)) {
      return $this->projectId;
    }

    $repoApi = new \Drupal\config_pr_gitlab\RepoControllers\GitLabApi($this->getClient());
    $path = '/api/v4/projects/?scope=projects&search=' . rawurlencode($this->repoName) . '&owned=true';
    $response = $repoApi->get($path);
    if (is_array($response)) {
      foreach ($response as $item) {
        if ($item['path'] == $this->repoName) {
          $this->projectId = $item['id'];
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
    $client = $this->getClient();
    $openPullRequests = $client->mergeRequests()->all($this->getProjectId(), ['state' => 'opened']);

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
    $repoApi = new \Drupal\config_pr\RepoControllers\GitLabApi($this->getClient());
    $path = '/api/v4/projects/' . $this->getProjectId();
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
      return $result['commit']['id'];
    }
  }

  /**
   * List branches.
   *
   * @return array
   *   The list of branches.
   */
  private function listBranches() {
    $branches = $this->getClient()->api('repo')->branches($this->getProjectId());

    return $branches;
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
    $branches = $this->listBranches();
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
        $branch = $this->getClient()->api('repo')->createBranch($this->getProjectId(), $branchName, $sha);
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
        ->api('merge_requests')->create($this->getProjectId(), $this->getDefaultBranch(), $branch, $title, null, null, $body);

      $pullRequest['number'] = $pullRequest['iid'];
      $pullRequest['url'] = $pullRequest['web_url'];

      return $pullRequest;
    }
    catch (GitLab\Exception\ValidationFailedException $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }
    catch (\Exception $e) {
      throw new \Exception($e->getMessage());
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
    catch (GitLab\Exception\RuntimeException $e) {
      throw new \Exception($e->getMessage());
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
        ->createFile($this->getProjectId(), $path, base64_encode($content), $branchName, $commitMessage, 'base64', $this->getCommitter()['email'], $this->getCommitter()['name']);

      return $result;
    }
    catch (GitLab\Exception\RuntimeException $e) {
      throw new \Exception($e->getMessage());
    }
    catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName) {
    try {
      $result = $this
        ->getClient()
        ->api('repo')
        ->updateFile($this->getProjectId(), $path, base64_encode($content), $branchName, $commitMessage, 'base64', $this->getCommitter()['email'], $this->getCommitter()['name']);

      return $result;
    }
    catch (GitLab\Exception\RuntimeException $e) {
      throw new \Exception($e->getMessage());
    }
    catch (\Exception $e) {
      throw new \Exception($e->getMessage());
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
        ->deleteFile($this->getProjectId(), $path, $branchName, $commitMessage, $this->getCommitter()['email'], $this->getCommitter()['name']);

      return $result;
    }
    catch (GitLab\Exception\RuntimeException $e) {
      throw new \Exception($e->getMessage());
    }
    catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }
  }

}
