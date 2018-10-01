<?php

namespace Drupal\config_pr;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Github\Client;
use Github\Api\GitData\References;

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

    $references = new References($client);
    $branches = $this->listBranches($references);
    $newBranch = $this->createBranch($references, 'test' . rand(1,1000));


    return;

    // Test create file
    $committer = array('name' => 'KnpLabs', 'email' => 'info@knplabs.com');

    $path = 'tests';
    $content = 'test';
    $commitMessage = 'Test commit';
    $branch = '7.x-1.x';

    //$client = $this->repoController->getClient();


//    $fileInfo = $client
//      ->api('repo')
//      ->contents()
//      ->create($this->username, $this->name, $path, $content, $commitMessage, $branch, $committer);

    //debug($branches);
    //debug($new_branch);

  }

  private function getDefaultBranch() {
    return '7.x-1.x'; //@todo hardcoded
  }

  private function getSha1($branch) {
    return '10382c0d19ab874e59eda139a8efe2cb9b53b7c5'; //@todo hardcoded
  }

  private function listBranches(\Github\Api\GitData\References $references) {
    $branches = $references->branches($this->username, $this->name);

    return $branches;
  }

  private function createBranch(\Github\Api\GitData\References $references, $branch) {
    $defaultBranch = $this->getDefaultBranch();

    $params = [
      'ref' => 'refs/heads/' . $branch,
      'sha' => $this->getSha1($defaultBranch),
    ];
    debug($params);return;
    $branch = $references->create($this->username, $this->name, $params);

    return $branch;
  }

  /**
   * {@inheritdoc}
   */
  public function createPr() {
    $pullRequest = $this->getClient()->api('pull_request')->create('ezsystems', 'ezpublish', array(
      'base'  => 'master',
      'head'  => 'testbranch',
      'title' => 'My nifty pull request',
      'body'  => 'This pull request contains a bunch of enhancements and bug-fixes, happily shared with you'
    ));
  }

}
