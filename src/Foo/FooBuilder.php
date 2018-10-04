<?php

namespace Drupal\config_pr\Foo;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\config_pr\Foo\Foo;
use Drupal\config_pr\Foo\FooBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;

/**
 * Class to define the term node foo builder.
 */
class FooBuilder implements FooBuilderInterface {
  use StringTranslationTrait;

  public $provider = 'I am Foo Builder';

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * An alias manager for looking up the system path.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(
    RequestContext $context,
    AliasManagerInterface $alias_manager,
    EntityTypeManagerInterface $entity_type_manager) {

    $this->context = $context;
    $this->aliasManager = $alias_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $path = '/'. trim($this->context->getPathInfo(), '/');
    $internal = $this->aliasManager->getPathByAlias($path);
    $parts = explode('/', trim($internal, '/'));
    $count = count($parts);
    if ($count == 3 && $parts[1] == 'term') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    try {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    } catch (InvalidPluginDefinitionException $e) {
      return NULL;
    } catch (PluginNotFoundException $e) {
      return NULL;
    }

    // This code is almost a direct copy of TermFooBuilder::build(),
    // the difference is that the term is loaded from the internal path
    // rather than the $route_match.

    $path = '/'. trim($this->context->getPathInfo(), '/');
    $internal = $this->aliasManager->getPathByAlias($path);
    $parts = explode('/', trim($internal, '/'));
    $count = count($parts);
    if ($count == 3 && $parts[1] == 'term') {
      if (!$term = Term::load($parts[2])) {
        return NULL;
      }

      $foo = new Foo();
      $foo->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

      // Foo needs to have terms cacheable metadata as a cacheable
      // dependency even though it is not shown in the foo because e.g. its
      // parent might have changed.
      $foo->addCacheableDependency($term);
      $parents = $term_storage->loadAllParents($term->id());
      // Remove current term being accessed.
      array_shift($parents);
      foreach (array_reverse($parents) as $term) {
        //$term = $this->entityTypeManager->getTranslationFromContext($term);
        $foo->addCacheableDependency($term);
        $foo->addLink(
          Link::createFromRoute($term->getName(),
            'entity.taxonomy_term.canonical',
            ['taxonomy_term' => $term->id()]
          )
        );
      }

      // This foo builder is based on a route parameter, and hence it
      // depends on the 'route' cache context.
      $foo->addCacheContexts(['route']);

      return $foo;
    }

    return NULL;
  }

}
