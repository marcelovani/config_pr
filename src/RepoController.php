<?php

namespace Drupal\config_pr;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Github\Client;
use Github\Api\GitData\References;
use Drupal\config_pr\Repo;

/**
 * Defines a base config_pr dumper implementation.
 *
 * @see \Drupal\config_pr\ConfigPrControllerInterface
 * @see plugin_api
 */
class RepoController implements RepoControllerInterface {
  /**
   * @var $username
   *   The repo user name
   */
  private $username;

  /**
   * @var $name
   *   The repo name
   */
  private $name;

  /**
   * @var $authToken
   *   The Authentication token
   */
  private $authToken;

  /**
   * @var $client
   *    The client instance
   */
  private $client;

  /**
   * {@inheritdoc}
   */
  public function setUsername($username) {
    $this->username = $username;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
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
    $this->client-> authenticate($this->authToken, null, Client::AUTH_URL_TOKEN);
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    if (!is_null($this->client)) {
      return $this->client;
    }

    $this->client = new Client();
    // @todo use try and catch for invalid authentication
    $this->authenticate();

    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenPrs() {
    $openPullRequests = $this->getClient()->api('pull_request')->all($this->username, $this->name, array('state' => 'open'));
    $result = [];
    foreach ($openPullRequests as $item) {
      $link = Link::fromTextAndUrl(
        'Open',
        Url::fromUri(
          $item['html_url'],
          array('attributes' => array(
            'target' => '_blank')
          )
        )
      );

      $result[] = [
        'id' => $item['id'],
        'title' => $item['title'],
        'link' => $link,
      ];
    }
//$this->testCreate();
    return $result;
  }

  /**
   * Get the default branch.
   */
  public function getDefaultBranch() {
    $repo = new Repo($this->getClient());
    $path = '/repos/'.rawurlencode($this->username).'/'.rawurlencode($this->name);
    $response = $repo->get($path);

    return $response['default_branch'];
  }

  /**
   * Get the Sha of the branch.
   *
   * @param $branch
   * @return mixed
   */
  private function getSha($branch) {
    if ($result = $this->findBranch($branch)) {
       return $result['object']['sha'];
    }
  }

  /**
   * List branches.
   *
   * @param References $references
   * @return array
   */
  private function listBranches(\Github\Api\GitData\References $references) {
    $branches = $references->branches($this->username, $this->name);

    return $branches;
  }

  /**
   * Checks if a branch exists.
   *
   * @param $branch
   */
  public function branchExists($branchName) {
    if ($this->findBranch($branchName)) {
      return TRUE;
    }
  }

  /**
   * Checks if a branch exists.
   *
   * @param $branch
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
   * @param $branchName
   * @return array
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

      $branch = $references->create($this->username, $this->name, $params);

      return $branch;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPr($base, $branch, $title, $body) {
    $pullRequest = $this->getClient()->api('pull_request')->create($this->username, $this->name, array(
      'base'  => $base,
      'head'  => $branch,
      'title' => $title,
      'body'  => $body,
      'ref'  => 'refs/head/' . $branch,
      'sha' => $this->getSha($branch),
    ));

    return $pullRequest;
  }

}
