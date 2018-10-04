<?php

namespace Drupal\config_pr\Form;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\config_pr\Repo\RepoManagerInterface;

/**
 * Construct the storage changes in a configuration synchronization form.
 */
class ConfigPrForm extends FormBase {

  /**
   * @var \Drupal\config_pr\Repo\RepoManagerInterface
   */
  protected $repo_provider;

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
   * @var ConfigFactoryInterface|\Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;
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
   * @param \Drupal\Core\Config\StorageInterface $sync_storage
   *   The source storage.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $snapshot_storage
   *   The snapshot storage.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   Configuration manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\config_pr\Repo\RepoManagerInterface $repo_provider
   *   The repo provider.
   */
  public function __construct(StorageInterface $sync_storage,
                              StorageInterface $active_storage,
                              StorageInterface $snapshot_storage,
                              EventDispatcherInterface $event_dispatcher,
                              ConfigManagerInterface $config_manager,
                              ConfigFactoryInterface $config_factory,
                              RepoManagerInterface $repo_provider) {
    $this->syncStorage = $sync_storage;
    $this->activeStorage = $active_storage;
    $this->snapshotStorage = $snapshot_storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->config = $config_factory;
    $this->repo_provider = $repo_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $repo_provider = $container->get('config.factory')->get('config_pr.settings')->get('repo.provider') ?? 'config_pr.repo_provider.github';
    return new static(
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('config.storage.snapshot'),
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('config.factory'),
      $container->get($repo_provider)
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
    $repo_user = $this->config('config_pr.settings')->get('repo.repo_user');
    $repo_name = $this->config('config_pr.settings')->get('repo.repo_name');
    if (empty($repo_user) || empty($repo_name)) {
      \Drupal::messenger()->addError($this->t('Repo configuration missing!'));
      return;
    }
    else {
      $this->repo_provider->setRepoUser($repo_user);
      $this->repo_provider->setRepoName($repo_name);
    }
    //@todo Use dependency injection.
    $user = User::load(\Drupal::currentUser()->id());
    $authToken = $user->field_config_pr_auth_token->value;
    if (empty($authToken)) {
      $uid = \Drupal::currentUser()->id();
      \Drupal::messenger()->addError($this->t('Config Pull Request Auth Token missing!'));
      $response = new RedirectResponse('/user/' . $uid . '/edit');
      $response->send();
    }
    else {
      $this->repo_provider->setAuthToken($authToken);
    }

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
          $config_diffs[$config_change_type][] = $config_name;
        }
      }
    }

    $form_state->set('config_diffs', $config_diffs);

    $form['new_pr'] = [
      '#title' => 'New Pull Request',
      '#type' => 'fieldset',
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
      '#value' => $this->t('Create Pull Request'),
    ];

    try {
      $form['open_pr_title'] = [
        '#markup' => '<h3>' . $this->t('Open Pull Requests') . '</h3>',
      ];
      $form['open_pr'] = [
        '#type' => 'table',
        '#header' => $this->getOpenPrTableHeader(),
        '#rows' => $this->repo_provider->getOpenPrs(),
        '#empty' => $this->t('There are no pull requests.'),
      ];
    } catch (\Github\Exception\RuntimeException $e) {
      \Drupal::messenger()->addError($e);
    }

    return $form;
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if branch exists.
    $branchName = $form_state->getValue('branch_name');
    if ($this->repo_provider->branchExists($branchName)) {
      $form_state->setErrorByName('branch_name', $this->t('The branch already exists.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create a branch.
    $branchName = $form_state->getValue('branch_name');
    $this->repo_provider->createBranch($branchName);

    // Create a pull request.
    // @todo should we use a batch here?
    if ($success = $this->commitConfig($branchName, $form_state)) {
      if ($pr = $this->createPr($branchName, $form_state)) {
        $link = Link::fromTextAndUrl(
          '#' . $pr['number'],
          Url::fromUri(
            $pr['html_url'],
            array(
              'attributes' => array(
                'target' => '_blank'
              )
            )
          )
        )->toString();

        \Drupal::messenger()->addStatus(t('Created pull request @link.', ['@link' => $link]));
      }
    }
  }

  /**
   * Creates a branch, commits the code and creates a pull request.
   *
   * @param $branchName
   * @param $form_state
   */
  private function commitConfig($branchName, $form_state) {
    //@todo Use dependency injection.
    $user = \Drupal::currentUser();
    $committer = array(
      'name' => $user->getAccountName(),
      'email' => $user->getEmail(),
    );
    $this->repo_provider->setCommitter($committer);

    $dir = trim(config_get_config_directory(CONFIG_SYNC_DIRECTORY), './');

    // Loop list of configs.
    foreach ($form_state->get('config_diffs') as $action => $configs) {
      foreach ($configs as $config_name) {

        // Test if the config was selected.
        $configId = 'selected-' . $this->getMachineName($config_name);
        $value = $form_state->getValue($configId);
        if ($value !== 1) {
          continue;
        }

        // Build commit message.
        if (!empty($this->config('config_pr.settings')->get('commit_messages.' . $action))) {
          $commitMessage = $this->t($this->config('config_pr.settings')
            ->get('commit_messages.' . $action), ['@action' => $action, '@config_name' => $config_name]);
        }
        else {
          $commitMessage = $this->t('Config @action config @config_name.yml', [
            '@action' => $action,
            '@config_name' => $config_name
          ]);
        };
        // Debug.
        //\Drupal::messenger()->addStatus(t('Performing @action on @conf.', ['@action' => $action, '@conf' => $config_name]));

        $path = $dir . '/' . $config_name . '.yml';
        $config = $this->activeStorage->read($config_name);
        $content = Yaml::encode($config);

        // Switch for diff type coming from the form
        switch ($action) {
          case 'rename';
            try {
              // @todo find a better way to get both names.
              $config_names = explode(' to ', $config_name);

              // Delete old file.
              $path = $dir . '/' . $config_names[0] . '.yml';
              $config = $this->activeStorage->read($config_names[0]);
              $this->repo_provider->deleteFile($path, $commitMessage, $branchName);

              // Create new file.
              $path = $dir . '/' . $config_names[1] . '.yml';
              $content = Yaml::encode($config);
              $this->repo_provider->createFile($path, $content, $commitMessage, $branchName);
            } catch (\Exception $e) {
              \Drupal::messenger()->addError($e->getMessage());
            }
            break;

          case 'delete';
            try {
              $this->repo_provider->deleteFile($path, $commitMessage, $branchName);
            } catch (\Exception $e) {
              \Drupal::messenger()->addError($e->getMessage());
            }
            break;

          case 'update';
            try {
              $this->repo_provider->updateFile($path, $content, $commitMessage, $branchName);
            } catch (\Exception $e) {
              \Drupal::messenger()->addError($e->getMessage());
            }
            break;

          case 'create';
            try {
              $this->repo_provider->createFile($path, $content, $commitMessage, $branchName);
            } catch (\Exception $e) {
              \Drupal::messenger()->addError($e->getMessage());
            }
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
    $result = $this->repo_provider->createPr(
      $this->repo_provider->getDefaultBranch(),
      $branchName,
      $form_state->getValue('pr_title'),
      $form_state->getValue('pr_description')
    );

    return $result;
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
