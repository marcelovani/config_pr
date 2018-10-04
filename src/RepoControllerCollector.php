<?php

namespace Drupal\config_pr;

use Drupal\Component\Assertion\Inspector;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Passes cache tag events to classes that wish to respond to them.
 */
class RepoControllerCollector implements RepoControllerCollectorInterface {

  use ContainerAwareTrait;

  /**
   * Holds an array of cache tags invalidators.
   *
   * @var \Drupal\config_pr\RepoControllerCollectorInterface[]
   */
  protected $repoCollectors = [];

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    assert(Inspector::assertAllStrings($tags), 'Cache tags must be strings.');

    // Notify all added cache tags invalidators.
    foreach ($this->repoCollector as $repoCollector) {
      $repoCollector->invalidateTags($tags);
    }

    // Additionally, notify each cache bin if it implements the service.
    foreach ($this->getInvalidatorCacheBins() as $bin) {
      $bin->invalidateTags($tags);
    }
  }

  /**
   * Reset statically cached tags in all cache tag checksum services.
   *
   * This is only used by tests.
   */
//  public function resetChecksums() {
//    foreach ($this->repoCollector as $repoCollector) {
//      if ($repoCollector instanceof CacheTagsChecksumInterface) {
//        $repoCollector->reset();
//      }
//    }
//  }

  /**
   * Adds a cache tags invalidator.
   *
   * @param \Drupal\config_pr\RepoControllerCollectorInterface $repoCollector
   *   A cache invalidator.
   */
  public function addRepoController(RepoControllerCollectorInterface $repoCollector) {
    $this->repoCollector[] = $repoCollector;
  }

  /**
   * Returns all cache bins that need to be notified about invalidations.
   *
   * @return \Drupal\config_pr\RepoControllerCollectorInterface[]
   *   An array of cache backend objects that implement the invalidator
   *   interface, keyed by their cache bin.
   */
  protected function getInvalidatorCacheBins() {
    $bins = [];
    foreach ($this->container->getParameter('cache_bins') as $service_id => $bin) {
      $service = $this->container->get($service_id);
      if ($service instanceof RepoControllerCollectorInterface) {
        $bins[$bin] = $service;
      }
    }
    return $bins;
  }

}
