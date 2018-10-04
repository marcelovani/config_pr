<?php

namespace Drupal\config_pr\Foo;

/**
 * Defines an interface a chained service that builds the foo.
 */
interface ChainFooBuilderInterface extends FooBuilderInterface {

  /**
   * Adds another foo builder.
   *
   * @param \Drupal\config_pr\Foo\FooBuilderInterface $builder
   *   The foo builder to add.
   * @param int $priority
   *   Priority of the foo builder.
   */
  public function addBuilder(FooBuilderInterface $builder, $priority);

}
