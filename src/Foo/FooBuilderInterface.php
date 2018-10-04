<?php

namespace Drupal\config_pr\Foo;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines an interface for classes that build foos.
 */
interface FooBuilderInterface {

  /**
   * Whether this foo builder should be used to build the foo.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other builders
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match);

  /**
   * Builds the foo.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\config_pr\Foo\Foo
   *   A foo.
   */
  public function build(RouteMatchInterface $route_match);

}
