<?php

namespace Drupal\config_pr\Repo;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Link;
use Drupal\Core\Render\RenderableInterface;

/**
 * Used to return generated repos with associated cacheability metadata.
 */
class Repo implements RenderableInterface, RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * An ordered list of links for the repo.
   *
   * @var \Drupal\Core\Link[]
   */
  protected $links = [];

  /**
   * Gets the repo links.
   *
   * @return \Drupal\Core\Link[]
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * Sets the repo links.
   *
   * @param \Drupal\Core\Link[] $links
   *   The repo links.
   *
   * @return $this
   *
   * @throws \LogicException
   *   Thrown when setting repo links after they've already been set.
   */
  public function setLinks(array $links) {
    if (!empty($this->links)) {
      throw new \LogicException('Once repo links are set, only additional repo links can be added.');
    }

    $this->links = $links;

    return $this;
  }

  /**
   * Appends a link to the end of the ordered list of repo links.
   *
   * @param \Drupal\Core\Link $link
   *   The link appended to the repo.
   *
   * @return $this
   */
  public function addLink(Link $link) {
    $this->links[] = $link;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    $build = [
      '#cache' => [
        'contexts' => $this->cacheContexts,
        'tags' => $this->cacheTags,
        'max-age' => $this->cacheMaxAge,
      ],
    ];
    if (!empty($this->links)) {
      $build += [
        '#theme' => 'repo',
        '#links' => $this->links,
      ];
    }
    return $build;
  }

}
