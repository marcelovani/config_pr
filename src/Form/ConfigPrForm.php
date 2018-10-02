<?php

namespace Drupal\config_pr\Form;

use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\config_pr\RepoControllerInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Construct the storage changes in a configuration synchronization form.
 */
class ConfigPrForm extends FormBase {
  /**
   * @var $repoController
   */
  protected $repoController;
  /**
   * The database lock object.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;
  /**
   * The sync configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncStorage;
  /**
   * The active configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;
  /**
   * The snapshot configuration object.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $snapshotStorage;
  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;
  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface;
   */
  protected $configManager;
  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;
  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;
  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Config\StorageInterface                        $sync_storage
   *   The source storage.
   * @param \Drupal\Core\Config\StorageInterface                        $active_storage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface                        $snapshot_storage
   *   The snapshot storage.
   * @param \Drupal\Core\Lock\LockBackendInterface                      $lock
   *   The lock object.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\Core\Config\ConfigManagerInterface                  $config_manager
   *   Configuration manager.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface             $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface               $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface             $module_installer
   *   The module installer.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface                $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Render\RendererInterface                       $renderer
   *   The renderer.
   * @param \Drupal\config_pr\RepoControllerInterface                   $repo_controller
   *   The repo controller.
   */
  public function __construct(StorageInterface $sync_storage, StorageInterface $active_storage, StorageInterface $snapshot_storage, LockBackendInterface $lock, EventDispatcherInterface $event_dispatcher, ConfigManagerInterface $config_manager, TypedConfigManagerInterface $typed_config, ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, ThemeHandlerInterface $theme_handler, RendererInterface $renderer, RepoControllerInterface $repo_controller) {
    $this->syncStorage = $sync_storage;
    $this->activeStorage = $active_storage;
    $this->snapshotStorage = $snapshot_storage;
    $this->lock = $lock;
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->typedConfigManager = $typed_config;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->themeHandler = $theme_handler;
    $this->renderer = $renderer;
    $this->repoController = $repo_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('config.storage.snapshot'),
      $container->get('lock.persistent'),
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler'),
      $container->get('renderer'),
      $container->get('config_pr.repo_controller')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_pr_form';
  }

  /**
   * Returns the config diff table header.
   *
   * @return array
   */
  private function getDiffTableHeader() {
    return [
      $this->t('Name'),
      $this->t('Operations'),
      $this->t('Include in Pull Request')
    ];
  }

  /**
   * Returns the open pull requests table header.
   *
   * @return array
   */
  private function getOpenPrTableHeader() {
    return [$this->t('Id'), $this->t('Title'), $this->t('Link')];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $source_list = $this->syncStorage->listAll();
    $storage_comparer = new StorageComparer($this->syncStorage, $this->activeStorage, $this->configManager);
    if (empty($source_list) || !$storage_comparer->createChangelist()
        ->hasChanges()
    ) {
      $form['no_changes'] = [
        '#type' => 'table',
        '#header' => $this->getDiffTableHeader(),
        '#rows' => [],
        '#empty' => $this->t('There are no configuration changes.'),
      ];
      $form['actions']['#access'] = FALSE;

      return $form;
    }

    // Store the comparer for use in the submit.
    $form_state->set('storage_comparer', $storage_comparer);
    $config_diffs = [];

    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      foreach ($storage_comparer->getChangelist(NULL, $collection) as $config_change_type => $config_names) {

        if (empty($config_names)) {
          continue;
        }

        // Invert delete and create. This is the opposite action when committing to the repo.
        if ($config_change_type == 'create') {
          $config_change_type = 'delete';
        }
        elseif ($config_change_type == 'delete') {
          $config_change_type = 'create';
        }

        $form[$collection][$config_change_type]['heading'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
        ];

        switch ($config_change_type) {
          case 'delete':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count removed', '@count removed');
            break;

          case 'update':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count changed', '@count changed');
            break;

          case 'create':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count new', '@count new');
            break;

          case 'rename':
            $form[$collection][$config_change_type]['heading']['#value'] = $this->formatPlural(count($config_names), '@count renamed', '@count renamed');
            break;
        }

        $form[$collection][$config_change_type]['list'] = [
          '#type' => 'table',
          '#header' => $this->getDiffTableHeader(),
        ];

        foreach ($config_names as $config_name) {
          if ($config_change_type == 'rename') {
            $names = $storage_comparer->extractRenameNames($config_name);
            $route_options = [
              'source_name' => $names['old_name'],
              'target_name' => $names['new_name']
            ];
            $config_name = $this->t('@source_name to @target_name', [
              '@source_name' => $names['old_name'],
              '@target_name' => $names['new_name']
            ]);
          }
          else {
            $route_options = ['source_name' => $config_name];
          }
          if ($collection != StorageInterface::DEFAULT_COLLECTION) {
            $route_name = 'config.diff_collection';
            $route_options['collection'] = $collection;
          }
          else {
            $route_name = 'config.diff';
          }

          $links['view_diff'] = [
            'title' => $this->t('View differences'),
            'url' => Url::fromRoute($route_name, $route_options),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => json_encode([
                'width' => 700
              ]),
            ],
          ];
          $configId = $this->getMachineName($config_name);

          $config_diffs[$config_change_type][] = $config_name;

          $form[$collection][$config_change_type]['list']['#rows'][] = [
            'name' => $config_name,
            'operations' => [
              'data' => [
                '#type' => 'operations',
                '#links' => $links,
              ],
            ],
            'pr' => [
              'data' => [
                '#name' => 'select-' . $configId,
                '#type' => 'checkbox',
                '#links' => $links,
              ],
            ],
          ];
          $form['select-items']['selected-' . $configId] = [
            '#type' => 'checkbox',
            '#title' => $config_name,
            '#title_display' => 'invisible',
            '#attributes' => [
              'style' => ['display: none;'],
            ],
            '#states' => [
              'checked' => [
                ':input[name*="select-' . $configId . '"]' => ['checked' => TRUE],
              ],
            ],
          ];
        }
      }
    }

    $form_state->set('config_diffs', $config_diffs);

    $form['new_pr'] = [
      '#title' => 'Pull Request',
      '#type' => 'fieldset',
    ];
    $form['new_pr']['pr_repo'] = [
      '#markup' => $this->t('Repository Url:') . ' ' . $this->config('config_pr.settings')
          ->get('repo_url'),
    ];
    $form['new_pr']['pr_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#required' => TRUE,
      '#description' => $this->t('Pull request title.'),
    ];
    // @todo display the machine name built form title with Edit link.
    $form['new_pr']['branch_name'] = [
      '#title' => $this->t('Branch name'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => date('Ymd', time()) . '-config',
      '#description' => $this->t('Branch name.'),
    ];
    $form['new_pr']['pr_description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#description' => $this->t('Pull request description.'),
    ];
    $form['new_pr']['actions'] = ['#type' => 'actions'];
    $form['new_pr']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pull Request'),
    ];

    $this->repoController->setUsername($this->config('config_pr.settings')
      ->get('repo.username'));
    $this->repoController->setName($this->config('config_pr.settings')
      ->get('repo.name'));
    $this->repoController->setAuthToken($this->config('config_pr.settings')
      ->get('repo.auth_token'));

    // @todo display friendly message for authentication exceptions
    $this->repoController->authenticate();

    $form['open_pr_title'] = [
      '#markup' => '<h3>' . $this->t('Open Pull Requests') . '</h3>',
    ];
    $form['open_pr'] = [
      '#type' => 'table',
      '#header' => $this->getOpenPrTableHeader(),
      '#rows' => $this->repoController->getOpenPrs(),
      '#empty' => $this->t('There are no pull requests.'),
    ];

    return $form;
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array                                $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Github authentication.
    //$this->repoController->authenticate();

    // Check if branch exists.
    $branchName = $form_state->getValue('branch_name');
    if ($this->repoController->branchExists($branchName)) {
      $form_state->setErrorByName('branch_name', $this->t('The branch already exists.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Github authentication.
    //$this->repoController->authenticate();

    // Create a branch.
    $branchName = $form_state->getValue('branch_name');
    $this->repoController->createBranch($branchName);

    // Create a pull request.
    if ($success = $this->commitConfig($branchName, $form_state)) {
      $pr = $this->createPr($branchName, $form_state);
      \Drupal::messenger()
        ->addStatus(t('Pull request created @id.', ['@id' => $pr[id]]));
    }

  }

  /**
   * Creates a branch, commits the code and creates a pull request.
   *
   * @param $branchName
   * @param $form_state
   */
  private function commitConfig($branchName, $form_state) {
    // Github authentication.
    //$this->repoController->authenticate();

    // Test create file
    $user = \Drupal::currentUser();
    $committer = array(
      'name' => $user->getAccountName(),
      'email' => $user->getEmail(),
    );

    $dir = 'config/sync'; //@todo get the config sync folder of the site.

    // Loop list of config selected.
    $result = NULL;
    foreach ($form_state->get('config_diffs') as $diffType => $configs) {
      foreach ($configs as $config_name) {

        $configId = 'selected-' . $this->getMachineName($config_name);
        $value = $form_state->getValue($configId);
        if ($value !== 1) {
          continue;
        }
        //echo 'Perform ' . $diffType . ' on ' . $config_name . ' config' . PHP_EOL;

        // Switch for diff type coming from the form
        $result = NULL;
        switch ($diffType) {
          case 'rename';
            // Command to rename file.
            break;

          case 'delete';
            // Command to delete file.
            break;

          case 'update';
            // Command to update file.
            break;

          case 'create';
            // Command to create file.
            $path = $dir . '/' . $config_name . '.yml';
            $config = $this->activeStorage->read($config_name);
            $content = Yaml::encode($config);
            $commitMessage = 'Config ' . $diffType;
            $client = $this->repoController->getClient();
            $result = $client
              ->api('repo')
              ->contents()
              ->create(
                $this->repoController->getUsername(),
                $this->repoController->getName(),
                $path,
                $content,
                $commitMessage,
                $branchName,
                $committer
              );
            break;
        }
      }
    }

    return TRUE;
  }

  /**
   * Creates a branch, commits the code and creates a pull request.
   *
   * @param $branchName
   * @param $form_state
   */
  private function createPr($branchName, $form_state) {
    // Create pull request.
    $this->repoController->createPr(
      $this->repoController->getDefaultBranch(),
      $branchName,
      $form_state->getValue('pr_title'),
      $form_state->getValue('pr_description')
    );
  }

  /**
   * Generates machine name from a string.
   *
   * @param $string
   *
   * @return mixed
   */
  private function getMachineName($string) {
    $string = preg_replace('/[^a-z0-9_]+/', '_', $string);

    return preg_replace('/_+/', '_', $string);
  }
}
