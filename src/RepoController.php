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
$this->testCreate($this->getClient());
    return $result;
  }

  private function testCreate(\Github\Client $client) {
    $branchName = 'test';
    $references = new References($client);
    if (!$this->createBranch($references, $branchName)) { //@todo name using Pr title replacing spaces with -
      // @todo display a message saying the branch already exists.
      return FALSE;
    }

    // Test create file
    $committer = array('name' => 'user', 'email' => 'user@email.com'); //@todo get user/email from logged in account

    $path = 'config/sync'; //@todo get the config sync folder of the site.

    // Loop list of config selected.

    // Switch for diff type coming from the form
    $diffType = 'new'; //@todo fix value for testing

    $result = NULL;
    switch ($diffType) {
      case 'rename';
        // Command to rename file.
        break;

      case 'delete';
        // Command to delete file.
        break;

      case 'update';
        // Command to update file.
        break;

      case 'new';
        // Command to create file.
        $content = 'test'; //@todo get content from new config
        $commitMessage = 'Test commit'; //@todo use title of pr or something better
        $result = $client
          ->api('repo')
          ->contents()
          ->create($this->username, $this->name, $path, $content, $commitMessage, $branchName, $committer);
        debug($result);
        break;
    }
    if ($result) {
      //@todo uncomment this
      //$this->createPr($this->getDefaultBranch(new Repo($this->getClient())), $branchName, '', '');
    }
  }

  /**
   * Get the default branch.
   *
   * @param Repo $repo
   * @return mixed
   */
  public function getDefaultBranch(\Drupal\config_pr\Repo $repo) {
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
  private function branchExists($branch) {
    if ($this->findBranch($branch)) {
      return TRUE;
    }
  }

  /**
   * Checks if a branch exists.
   *
   * @param $branch
   */
  private function findBranch($branch) {
    $references = new References($this->getClient());
    $branches = $this->listBranches($references);
    foreach ($branches as $item) {
      if ($item['ref'] == 'refs/heads/' . $branch) {
        return $item;
      }
    }
  }

  /**
   * Creates a new branch from the default branch.
   *
   * @param References $references
   * @param $branch
   * @return array
   */
  private function createBranch(\Github\Api\GitData\References $references, $branch) {
    $defaultBranch = $this->getDefaultBranch(new Repo($this->getClient()));

    if ($sha = $this->getSha($defaultBranch)) {
      $params = [
        'ref' => 'refs/heads/' . $branch,
        'sha' => $sha,
      ];

      if ($this->branchExists($branch)) {
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
      'title' => 'My nifty pull request', //@todo get value from form
      'body'  => 'This pull request contains a bunch of enhancements and bug-fixes, happily shared with you'  //@todo get value from form
    ));
    debug($pullRequest);
  }

}
