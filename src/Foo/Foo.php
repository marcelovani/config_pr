<?php

namespace Drupal\config_pr\Foo;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Link;
use Drupal\Core\Render\RenderableInterface;

/**
 * Used to return generated foos with associated cacheability metadata.
 */
class Foo implements RenderableInterface, RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * An ordered list of links for the foo.
   *
   * @var \Drupal\Core\Link[]
   */
  protected $links = [];

  /**
   * Gets the foo links.
   *
   * @return \Drupal\Core\Link[]
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * Sets the foo links.
   *
   * @param \Drupal\Core\Link[] $links
   *   The foo links.
   *
   * @return $this
   *
   * @throws \LogicException
   *   Thrown when setting foo links after they've already been set.
   */
  public function setLinks(array $links) {
    if (!empty($this->links)) {
      throw new \LogicException('Once foo links are set, only additional foo links can be added.');
    }

    $this->links = $links;

    return $this;
  }

  /**
   * Appends a link to the end of the ordered list of foo links.
   *
   * @param \Drupal\Core\Link $link
   *   The link appended to the foo.
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
        '#theme' => 'foo',
        '#links' => $this->links,
      ];
    }
    return $build;
  }

}
