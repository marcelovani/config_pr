<?php

namespace Drupal\config_pr_bitbucket\RepoControllers;

use Drupal\config_pr\RepoControllerInterface;
use Bitbucket\Client;

/**
 * Class to define the BitBucket controller.
 *
 * @see \Drupal\config_pr\RepoControllerInterface
 */
class BitBucketController implements RepoControllerInterface {

  /**
   * Holds the controller name.
   *
   * @var string $name.
   */
  protected $name = 'BitBucket';

  /**
   * Holds the controller Id.
   *
   * @var string $id.
   */
  protected $id = 'config_pr_bitbucket.repo_controller.bitbucket';

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

  public function getOpenPrs() {
    $result = [];
    $client = $this->getClient();

    // @todo filter by open only
    $openPullRequests = $client
      ->repositories()
      ->users($this->getRepoUser())
      ->pullRequests($this->getRepoName());
    $openPullRequests = $openPullRequests->list([]);
print_r($openPullRequests);exit;
exit;
    foreach ($openPullRequests as $item) {
      $link = Link::fromTextAndUrl(
        'Open',
        Url::fromUri(
          $item['web_url'],
          array(
            'attributes' => array(
              'target' => '_blank'
            )
          )
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
   * {@inheritdoc}
   */
  public function setCommitter($committer) {
    $this->committer = $committer;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoUser() {
    return $this->repo_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepoName() {
    return $this->repo_name;
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
  public function branchExists($branchName) {}

  /**
   * {@inheritdoc}
   */
  public function getSha($branch) {}

  /**
   * {@inheritdoc}
   */
  public function setRepoUser($repo_user) {
    $this->repo_user = $repo_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setRepoName($repo_name) {
    $this->repo_name = $repo_name;
  }

  /**
   * {@inheritdoc}
   */
  public function updateFile($path, $content, $commitMessage, $branchName) {}

  /**
   * {@inheritdoc}
   */
  public function createBranch($branchName) {}

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    var_dump($this->authToken);
    $this->getClient()->authenticate(Client::AUTH_OAUTH_TOKEN, $this->authToken);
    var_dump($this->getClient()->currentUser()->show());
    exit;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultBranch() {}

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body) {}

  /**
   * {@inheritdoc}
   */
  public function createFile($path, $content, $commitMessage, $branchName) {}

    /**
   * {@inheritdoc}
   */
  public function deleteFile($path, $commitMessage, $branchName) {}

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    if (!is_null($this->client)) {
      return $this->client;
    }

    $this->client = new Client();
    $this->authenticate();

    return $this->client;
  }

}
