<?php

namespace Drupal\config_pr;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Github\Client;

/**
 * Defines a base config_pr dumper implementation.
 *
 * @see \Drupal\config_pr\ConfigPrControllerInterface
 * @see plugin_api
 */
class RepoController extends PluginBase implements RepoControllerInterface {
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
    $this->authenticate($this->authToken, null, Client::AUTH_URL_TOKEN);
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

    return $result;
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
