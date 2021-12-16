<?php

namespace Drupal\nopremium\Form;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nopremium_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nopremium.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('nopremium.settings');

    $form['messages'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Premium messages'),
      '#description' => $this->t('You may customize the messages displayed to unprivileged users trying to view full premium contents.'),
    ];
    $form['messages']['default_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default message'),
      '#description' => $this->t('This message will apply to all content types with blank messages below.'),
      '#default_value' => $config->get('default_message'),
      '#rows' => 3,
      '#required' => TRUE,
    ];
    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $content_type) {
      $form['messages']['message_' . $content_type->id()] = [
        '#type' => 'textarea',
        '#title' => $this->t('Message for %type content type', ['%type' => $content_type->label()]),
        '#default_value' => $config->get('messages.' . $content_type->id()),
        '#rows' => 3,
      ];
    }
    if ($this->moduleHandler->moduleExists('token')) {
      $form['messages']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['user', 'node'],
        '#weight' => 90,
      ];
    }
    else {
      $form['messages']['token_tree'] = [
        '#markup' => '<p>' . $this->t('Enable the <a href="@drupal-token">Token module</a> to view the available token browser.', ['@drupal-token' => 'http://drupal.org/project/token']) . '</p>',
      ];
    }
    $options = [];
    foreach ($this->entityDisplayRepository->getViewModes('node') as $id => $view_mode) {
      $options[$id] = $view_mode['label'];
    }
    $form['view_modes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Premium display modes'),
      '#description' => $this->t('Select for which view modes access is restricted. When none are selected, all are restricted except the view mode that is selected as "@teaser_view_mode".', [
        '@teaser_view_mode' => $this->t('Teaser display mode'),
      ]),
      '#default_value' => $config->get('view_modes'),
      '#options' => $options,
    ];
    $form['teaser_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Teaser display mode'),
      '#description' => $this->t('Teaser display view mode to render for premium contents.'),
      '#default_value' => $config->get('teaser_view_mode'),
      '#options' => $options,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('nopremium.settings')
      ->set('default_message', $values['default_message'])
      ->set('view_modes', array_filter($values['view_modes']))
      ->set('teaser_view_mode', $values['teaser_view_mode'])
      ->save();
    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $content_type) {
      $this->config('nopremium.settings')
        ->set('messages.' . $content_type->id(), $values['message_' . $content_type->id()])
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
