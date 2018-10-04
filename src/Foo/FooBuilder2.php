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
class FooBuilder2 implements FooBuilderInterface {
  use StringTranslationTrait;

  protected $name = 'GitLabs';
  protected $id = 'config_pr.foo.provider2';

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
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
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
  public function build() {
    return $this->getName();
  }

}