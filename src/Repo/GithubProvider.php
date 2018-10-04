<?php

namespace Drupal\config_pr\Repo;

/**
 * Class to define the term node repo provider.
 */
class GithubProvider implements RepoManagerInterface {

  /**
   * Holds the provider name.
   *
   * @var string $name.
   */
  protected $name = 'Github';

  /**
   * Holds the provider Id.
   *
   * @var string $id.
   */
  protected $id = 'config_pr.repo_provider.github';

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

}
