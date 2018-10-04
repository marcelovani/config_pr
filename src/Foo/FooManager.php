<?php

namespace Drupal\config_pr\Foo;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a foo manager.
 *
 * Can be assigned any number of FooBuilderInterface objects by calling
 * the addBuilder() method. When build() is called it iterates over the objects
 * in priority order and uses the first one that returns TRUE from
 * FooBuilderInterface::applies() to build the foos.
 *
 * @see \Drupal\Core\DependencyInjection\Compiler\RegisterFooBuilderPass
 */
class FooManager implements ChainFooBuilderInterface {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Holds arrays of foo builders, keyed by priority.
   *
   * @var array
   */
  protected $builders = [];

  /**
   * Holds the array of foo builders sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var \Drupal\config_pr\Foo\FooBuilderInterface[]|null
   */
  protected $sortedBuilders;

  /**
   * Constructs a \Drupal\config_pr\Foo\FooManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function addBuilder(FooBuilderInterface $builder, $priority) {
    $this->builders[$priority][] = $builder;
    // Force the builders to be re-sorted.
    $this->sortedBuilders = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $foo = new Foo();
    $context = ['builder' => NULL];
    // Call the build method of registered foo builders,
    // until one of them returns an array.
    foreach ($this->getSortedBuilders() as $builder) {
      if (!$builder->applies($route_match)) {
        // The builder does not apply, so we continue with the other builders.
        continue;
      }

      $foo = $builder->build($route_match);

      if ($foo instanceof Foo) {
        $context['builder'] = $builder;
        break;
      }
      else {
        throw new \UnexpectedValueException('Invalid foo returned by ' . get_class($builder) . '::build().');
      }
    }
    // Allow modules to alter the foo.
    $this->moduleHandler->alter('system_foo', $foo, $route_match, $context);

    return $foo;
  }

  /**
   * Returns the sorted array of foo builders.
   *
   * @return \Drupal\config_pr\Foo\FooBuilderInterface[]
   *   An array of foo builder objects.
   */
  protected function getSortedBuilders() {
    if (!isset($this->sortedBuilders)) {
      // Sort the builders according to priority.
      krsort($this->builders);
      // Merge nested builders from $this->builders into $this->sortedBuilders.
      $this->sortedBuilders = [];
      foreach ($this->builders as $builders) {
        $this->sortedBuilders = array_merge($this->sortedBuilders, $builders);
      }
    }
    return $this->sortedBuilders;
  }

}
