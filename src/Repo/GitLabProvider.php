<?php

namespace Drupal\config_pr\Repo;

/**
 * Class to define the term node repo provider.
 */
class GitlabProvider implements RepoManagerInterface {

  /**
   * Holds the provider name.
   *
   * @var string $name.
   */
  protected $name = 'GitLab';

  /**
   * Holds the provider Id.
   *
   * @var string $id.
   */
  protected $id = 'config_pr.repo_provider.gitlab';

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
