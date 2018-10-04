<?php

namespace Drupal\config_pr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\config_pr\RepoControllerInterface;
use Drupal\config_pr\Foo\FooBuilderInterface;

class ConfigPrSettingsForm extends ConfigFormBase {
  /**
   * @var $repoController
   */
  protected $repoController;

  protected $foo_manager;

  /**
   * Constructs a ConfigPrSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\config_pr\RepoControllerInterface  $repo_controller
   *   The repo controller.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RepoControllerInterface $repo_controller, FooBuilderInterface $foo_manager) {
    parent::__construct($config_factory);
    $this->repoController = $repo_controller;
    $this->foo_manager = $foo_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    //$conf = $this->config('which_repo')->getName();
    return new static(
      $container->get('config.factory'),
      $container->get('config_pr.github_controller'), //@todo This will be replaced with the one below
      $container->get('config_pr.foo')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_pr_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config_pr.settings'];
  }

  /**
   * Configuration form.
   *
   * @param array                                $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array The form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //@todo Add support to other repos. Currently only works with Github.
    $form['repo'] = [
      '#title' => $this->t('Repository'),
      '#type' => 'fieldset',
      '#description' => '<strong>' . $this->t('Note: Only Github is currently supported.') . '</strong>',
    ];
    $form['repo']['repo_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Repo provier'),
      '#description' => $this->t('Select provider.'),
      '#options' => $this->foo_manager->getBuilderNames(),
      '#default_value' => $this->config('config_pr.settings')->get('repo.provider'),
      '#required' => TRUE,
    ];
    // Try to get the information from the local repo.
    $repo_info = $this->repoController->getLocalRepoInfo();
    $form['repo']['repo_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Repo Username'),
      '#description' => $this->t('Enter the repo username.'),
      //'#default_value' => $this->config('config_pr.settings')->get('repo.username'),
      '#default_value' => $this->config('config_pr.settings')->get('repo.username') ?? $repo_info['username'],
      '#required' => TRUE,
    ];
    $form['repo']['repo_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Repo Name'),
      '#description' => $this->t('Enter the repo name.'),
      '#default_value' => $this->config('config_pr.settings')->get('repo.name') ?? $repo_info['name'],
      '#required' => TRUE,
    ];
    $form['commit_messages'] = [
      '#title' => $this->t('Commit messages'),
      '#type' => 'fieldset',
      '#description' => $this->t('Available tokens: @config_name'),
    ];
    $form['commit_messages']['message_create'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Creating files'),
      '#description' => $this->t('Enter the commit message.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('commit_messages.create') ?? $this->t('Created config @config_name.yml'),
      '#required' => TRUE,
    ];
    $form['commit_messages']['message_delete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deleting files'),
      '#description' => $this->t('Enter the commit message.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('commit_messages.delete') ?? $this->t('Deleted config @config_name.yml'),
      '#required' => TRUE,
    ];
    $form['commit_messages']['message_update'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Updating files'),
      '#description' => $this->t('Enter the commit message.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('commit_messages.update') ?? $this->t('Updated config @config_name.yml'),
      '#required' => TRUE,
    ];
    $form['commit_messages']['message_rename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Renaming files'),
      '#description' => $this->t('Enter the commit message.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('commit_messages.rename') ?? $this->t('Renamed config from @config_name.yml'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validator.
   *
   * @param array                                $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('config_pr.settings');
    $config->set('repo.provider', $form_state->getValue('repo_provider'));
    $config->set('repo.username', $form_state->getValue('repo_username'));
    $config->set('repo.name', $form_state->getValue('repo_name'));
    $config->set('commit_messages.update', $form_state->getValue('message_update'));
    $config->set('commit_messages.create', $form_state->getValue('message_create'));
    $config->set('commit_messages.delete', $form_state->getValue('message_delete'));
    $config->set('commit_messages.rename', $form_state->getValue('message_rename'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
