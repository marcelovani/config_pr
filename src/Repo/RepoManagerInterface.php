<?php

namespace Drupal\config_pr\Repo;

interface RepoManagerInterface {

  public function getName();

  public function getId();

  public function build();

}
