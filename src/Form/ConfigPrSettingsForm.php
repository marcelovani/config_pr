<?php

namespace Drupal\config_pr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigPrSettingsForm extends ConfigFormBase {

  /**
   * Constructs a ConfigPrSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
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
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @return array The form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //@todo Add support to other repos. Currently only works with Github.
    $form['repo'] = [
      '#title' => $this->t('Repository'),
      '#type' => 'fieldset',
      '#description' => '<strong>' . $this->t('Note: Only Github is currently supported.') . '</strong>',
    ];
    $form['repo']['repo_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url'),
      '#description' => $this->t('Enter the repo url.'),
      '#default_value' => $this->config('config_pr.settings')->get('repo_url'),
      '#required' => TRUE,
    ];
    $form['repo']['repo_auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authentication token'),
      '#description' => $this->t('Enter the repo authentication token.'),
      '#default_value' => $this->config('config_pr.settings')->get('repo_auth_token'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validator.
   *
   * @param array $form
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
    $config->set('repo_url', $form_state->getValue('repo_url'));
    $config->set('repo_auth_token', $form_state->getValue('repo_auth_token'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
