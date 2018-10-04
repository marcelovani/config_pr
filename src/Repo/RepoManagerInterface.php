<?php

namespace Drupal\config_pr\Repo;

interface RepoManagerInterface {

  /**
   * Get the provider name.
   *
   * @return string
   *    Provider name
   */
  public function getName();

  /**
   * Get the service id.
   * This matches the id found in services.yml.
   *
   * @return string
   *    The id
   */
  public function getId();

}
