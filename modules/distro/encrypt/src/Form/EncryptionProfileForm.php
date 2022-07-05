<?php

namespace Drupal\encrypt\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\encrypt\EncryptService;
use Drupal\encrypt\Plugin\EncryptionMethodPluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the form to add / edit an EncryptionProfile entity.
 *
 * @package Drupal\encrypt\Form
 */
class EncryptionProfileForm extends EntityForm {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The EncryptService definition.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryptService;

  /**
   * Keeps track of extra confirmation step on profile edit.
   *
   * @var bool
   */
  protected $editConfirmed = FALSE;

  /**
   * The original encryption profile.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile|null
   *   The original EncryptionProfile entity or NULL if this is a new one.
   */
  protected $originalProfile = NULL;

  /**
   * Constructs a EncryptionProfileForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Encrypt\EncryptService $encrypt_service
   *   The lazy context repository service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptService $encrypt_service) {
    $this->configFactory = $config_factory;
    $this->encryptService = $encrypt_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // If the form is rebuilding.
    if ($form_state->isRebuilding()) {

      // If an encryption method change triggered the rebuild.
      if ($form_state->getTriggeringElement()['#name'] == 'encryption_method') {
        // Update the encryption method plugin.
        $this->updateEncryptionMethod($form_state);
      }
    }
    elseif ($this->operation == "edit") {
      // Only when the form is first built.
      /* @var $encryption_profile \Drupal\encrypt\Entity\EncryptionProfile */
      $encryption_profile = $this->entity;
      $this->originalProfile = clone $encryption_profile;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var $encryption_profile \Drupal\encrypt\Entity\EncryptionProfile */
    $encryption_profile = $this->entity;

    // If the profile is being edited and editing has not been confirmed yet,
    // display a warning and require confirmation.
    if ($this->operation == "edit" && !$this->editConfirmed) {
      $form['confirm_edit'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Be extremely careful when editing an encryption profile! It may result in making data encrypted with this profile unreadable. Are you sure you want to edit this profile?'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      return $form;
    }

    // If editing has been confirmed, display the edit form.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $encryption_profile->label(),
      '#description' => $this->t("Label for the encryption profile."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $encryption_profile->id(),
      '#machine_name' => [
        'exists' => '\Drupal\encrypt\Entity\EncryptionProfile::load',
      ],
      '#disabled' => !$encryption_profile->isNew(),
    ];

    // This is the element that contains all of the dynamic parts of the form.
    $form['encryption'] = [
      '#type' => 'container',
      '#prefix' => '<div id="encrypt-settings">',
      '#suffix' => '</div>',
    ];

    $encryption_methods = $this->encryptService->loadEncryptionMethods(FALSE);
    $method_options = [];
    // Show the current encryption plugin, even if deprecated.
    if (!$encryption_profile->isNew()) {
      $method = $encryption_profile->getEncryptionMethod();
      $method_options[$method->getPluginId()] = $method->getLabel();
    }
    foreach ($encryption_methods as $plugin_id => $definition) {
      $method_options[$plugin_id] = (string) $definition['title'];
    }
    $form['encryption']['encryption_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Encryption Method'),
      '#description' => $this->t('Select the method used for encryption'),
      '#options' => $method_options,
      '#required' => TRUE,
      '#default_value' => $encryption_profile->getEncryptionMethodId(),
      '#ajax' => [
        'callback' => [$this, 'ajaxUpdateSettings'],
        'event' => 'change',
        'wrapper' => 'encrypt-settings',
      ],
    ];

    $form['encryption']['encryption_method_configuration'] = [
      '#type' => 'container',
      '#title' => $this->t('Encryption method settings'),
      '#title_display' => FALSE,
      '#tree' => TRUE,
    ];
    if ($encryption_profile->getEncryptionMethod() instanceof EncryptionMethodPluginFormInterface) {
      $plugin_form_state = $this->createPluginFormState($form_state);
      $form['encryption']['encryption_method_configuration'] += $encryption_profile->getEncryptionMethod()->buildConfigurationForm([], $plugin_form_state);
      $form_state->setValue('encryption_method_configuration', $plugin_form_state->getValues());
    }

    $form['encryption']['encryption_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Encryption Key'),
      '#required' => TRUE,
      '#default_value' => $encryption_profile->getEncryptionKeyId(),
    ];

    // Filter the list of available keys by the "encryption" key type group.
    $key_filters = ['type_group' => 'encryption'];

    $form['encryption']['encryption_key']['#key_filters'] = $key_filters;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    // If the profile is being edited and editing has not been confirmed yet.
    if ($this->operation == "edit" && !$this->editConfirmed) {
      return [
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Edit'),
          '#button_type' => 'primary',
          '#submit' => [
            [$this, 'confirmEdit'],
          ],
        ],
        'cancel' => [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#attributes' => ['class' => ['button']],
          '#url' => Url::fromRoute('entity.encryption_profile.collection'),
          '#cache' => [
            'contexts' => [
              'url.query_args:destination',
            ],
          ],
        ],
      ];
    }
    else {
      return parent::actions($form, $form_state);
    }
  }

  /**
   * Creates a FormStateInterface object for a plugin.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to copy values from.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   A clone of the form state object with values from the plugin.
   */
  protected function createPluginFormState(FormStateInterface $form_state) {
    // Clone the form state.
    $plugin_form_state = clone $form_state;

    // Clear the values, except for this plugin type's settings.
    $plugin_form_state->setValues($form_state->getValue('encryption_method_configuration', []));

    return $plugin_form_state;
  }

  /**
   * AJAX callback to update the dynamic settings on the form.
   *
   * @param array $form
   *   The form definition array for the encryption profile form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element to update in the form.
   */
  public function ajaxUpdateSettings(array &$form, FormStateInterface $form_state) {
    return $form['encryption'];
  }

  /**
   * Update the EncryptionMethod plugin.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function updateEncryptionMethod(FormStateInterface $form_state) {
    /* @var $encryption_profile \Drupal\encrypt\Entity\EncryptionProfile */
    $encryption_profile = $this->entity;

    /* @var $plugin \Drupal\encrypt\EncryptionMethodInterface */
    $plugin = $encryption_profile->getEncryptionMethod();

    $encryption_profile->setEncryptionMethod($plugin);

    // If an original profile exists and the plugin ID matches the existing one.
    if ($this->originalProfile && $this->originalProfile->getEncryptionMethod()->getPluginId() == $plugin->getPluginId()) {
      // Use the configuration from the original profile's plugin.
      $configuration = $this->originalProfile->getEncryptionMethod()->getConfiguration();
    }
    else {
      // Use the plugin's default configuration.
      $configuration = $plugin->defaultConfiguration();
    }

    $plugin->setConfiguration($configuration);
    $form_state->setValue('encryption_method_configuration', []);
    $form_state->getUserInput()['encryption_method_configuration'] = [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Only validate when submitting the form, not on AJAX rebuild.
    if (!$form_state->isSubmitted()) {
      return;
    }

    // If the profile is being edited and editing has not been confirmed yet.
    if ($this->operation == "edit" && !$this->editConfirmed) {
      return;
    }

    // If the encryption method contains a config form, validate it as well.
    if ($plugin = $this->entity->getEncryptionMethod()) {
      if ($plugin instanceof EncryptionMethodPluginFormInterface) {
        $plugin_form_state = $this->createPluginFormState($form_state);
        $plugin->validateConfigurationForm($form, $plugin_form_state);
        $form_state->setValue('encryption_method_configuration', $plugin_form_state->getValues());
        $this->moveFormStateErrors($plugin_form_state, $form_state);
        $this->moveFormStateStorage($plugin_form_state, $form_state);
      }
    }

    $form_state->cleanValues();
    /** @var \Drupal\encrypt\Entity\EncryptionConfiguration $entity */
    $this->entity = $this->buildEntity($form, $form_state);

    // Validate the EncryptionProfile entity.
    $errors = $this->entity->validate();
    if ($errors) {
      $form_state->setErrorByName('encryption_key', implode(';', $errors));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit plugin configuration if available.
    if ($plugin = $this->entity->getEncryptionMethod()) {
      if ($plugin instanceof EncryptionMethodPluginFormInterface) {
        $plugin_form_state = $this->createPluginFormState($form_state);
        $plugin->submitConfigurationForm($form, $plugin_form_state);
        $form_state->setValue('encryption_method_configuration', $plugin_form_state->getValues());
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for the edit confirmation button.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function confirmEdit(array &$form, FormStateInterface $form_state) {
    $this->editConfirmed = TRUE;
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $encryption_profile = $this->entity;
    $status = $encryption_profile->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label encryption profile.', [
        '%label' => $encryption_profile->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label encryption profile was not saved.', [
        '%label' => $encryption_profile->label(),
      ]));
    }
    $form_state->setRedirectUrl($encryption_profile->toUrl('collection'));
  }

  /**
   * Moves form errors from one form state to another.
   *
   * @param \Drupal\Core\Form\FormStateInterface $from
   *   The form state object to move from.
   * @param \Drupal\Core\Form\FormStateInterface $to
   *   The form state object to move to.
   */
  protected function moveFormStateErrors(FormStateInterface $from, FormStateInterface $to) {
    foreach ($from->getErrors() as $name => $error) {
      $to->setErrorByName($name, $error);
    }
  }

  /**
   * Moves storage variables from one form state to another.
   *
   * @param \Drupal\Core\Form\FormStateInterface $from
   *   The form state object to move from.
   * @param \Drupal\Core\Form\FormStateInterface $to
   *   The form state object to move to.
   */
  protected function moveFormStateStorage(FormStateInterface $from, FormStateInterface $to) {
    foreach ($from->getStorage() as $index => $value) {
      $to->set($index, $value);
    }
  }

}
