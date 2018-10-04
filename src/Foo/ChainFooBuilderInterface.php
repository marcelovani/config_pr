<?php

namespace Drupal\config_pr\Foo;

/**
 * Defines an interface a chained service that builds the foo.
 */
interface ChainFooBuilderInterface extends FooBuilderInterface {

  public function getBuilderNames();

  /**
   * Adds another foo builder.
   *
   * @param \Drupal\config_pr\Foo\FooBuilderInterface $builder
   *   The foo builder to add.
   */
  public function addBuilder(FooBuilderInterface $builder);

}
