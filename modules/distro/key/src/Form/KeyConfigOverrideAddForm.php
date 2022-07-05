<?php

namespace Drupal\key\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * KeyConfigOverrideAddForm class.
 */
class KeyConfigOverrideAddForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The Key Configuration Override entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The configuration entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $configEntityTypeDefinitions;

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a KeyConfigOverrideAddForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, StorageInterface $config_storage, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->configStorage = $config_storage;
    $this->storage = $entity_type_manager->getStorage('key_config_override');
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('config.storage'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'key_config_override_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config_type = $form_state->getValue('config_type');
    $config_name = $form_state->getValue('config_name');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key configuration override name'),
      '#description' => $this->t('A human readable name for this override.'),
      '#size' => 30,
      '#maxlength' => 64,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#required' => TRUE,
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => [
        'exists' => [$this->storage, 'load'],
      ],
    ];

    $entity_types = array_map(function (EntityTypeInterface $definition) {
      return $definition->getLabel();
    }, $this->getConfigEntityTypeDefinitions());

    // Sort the entity types by label.
    uasort($entity_types, 'strnatcasecmp');

    // Add the simple configuration type to the top of the list.
    $config_types = [
      'system.simple' => $this->t('Simple configuration'),
    ] + $entity_types;

    $form['config_type'] = [
      '#title' => $this->t('Configuration type'),
      '#type' => 'select',
      '#options' => $config_types,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::changeConfigObject',
        'wrapper' => 'edit-config-object-wrapper',
      ],
    ];

    $form['config_object'] = [
      '#type' => 'container',
      '#prefix' => '<div id="edit-config-object-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['config_object']['config_name'] = [
      '#title' => $this->t('Configuration name'),
      '#type' => 'select',
      '#options' => $this->getConfigNames($config_type),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::changeConfigObject',
        'wrapper' => 'edit-config-object-wrapper',
      ],
    ];

    $form['config_object']['config_item'] = [
      '#title' => $this->t('Configuration item'),
      '#type' => 'select',
      '#options' => $this->getConfigItems($config_type, $config_name),
      '#required' => TRUE,
    ];

    $request = $this->requestStack->getCurrentRequest();
    $query_key = $request->query->get('key');
    $form['key_id'] = [
      '#title' => $this->t('Key'),
      '#type' => 'key_select',
      '#default_value' => $query_key,
      '#required' => TRUE,
    ];

    $form['clear_overridden'] = [
      '#title' => $this->t('Clear overridden value'),
      '#type' => 'checkbox',
      '#description' => $this->t('Check this field to clear any existing value for the overridden configuration item. This is important to make sure potentially sensitive data is removed from the configuration.'),
      '#default_value' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Add the entity prefix when the form is submitted.
    if ($form_state->isSubmitted()) {
      $definitions = $this->getConfigEntityTypeDefinitions();
      $config_type = $form_state->getValue('config_type');

      if (array_key_exists($config_type, $definitions)) {
        $config_prefix = $definitions[$config_type]->getConfigPrefix();
      }
      else {
        $config_prefix = '';
      }

      $form_state->setValue('config_prefix', $config_prefix);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    $saved = parent::save($form, $form_state);

    // Clear the overridden value, if requested.
    if ($saved && $form_state->getValue('clear_overridden')) {
      $override = $this->entity;

      $type = $override->getConfigType();
      $name = $override->getConfigName();
      $item = $override->getConfigItem();

      if ($type !== 'system.simple') {
        $definition = $this->entityTypeManager->getDefinition($type);
        $name = $definition->getConfigPrefix() . '.' . $name;
      }

      $config = $this->configFactory()->getEditable($name);
      $config->set($item, NULL);
      $config->save();
    }

    return $saved;
  }

  /**
   * Updates the configuration object container element.
   */
  public function changeConfigObject($form, FormStateInterface $form_state) {
    return $form['config_object'];
  }

  /**
   * Get the configuration entity type definitions.
   *
   * @param bool $with_excluded
   *   Whether or not to include excluded configuration types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   The entity type definitions.
   */
  protected function getConfigEntityTypeDefinitions($with_excluded = FALSE) {
    if (!isset($this->configEntityTypeDefinitions)) {
      $config_entity_type_definitions = [];
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $definition) {
        if ($definition->entityClassImplements(ConfigEntityInterface::class)) {
          $config_entity_type_definitions[$entity_type] = $definition;
        }
      }
      $this->configEntityTypeDefinitions = $config_entity_type_definitions;
    }

    if ($with_excluded) {
      $definitions = $this->configEntityTypeDefinitions;
    }
    else {
      $definitions = array_diff_key($this->configEntityTypeDefinitions, $this->excludedConfigTypes());
    }

    return $definitions;
  }

  /**
   * Get the configuration names for a specified configuration type.
   *
   * @param string|null $config_type
   *   The configuration type.
   *
   * @return array
   *   The configuration names.
   */
  protected function getConfigNames($config_type = NULL) {
    $names = [
      '' => $this->t('- Select -'),
    ];

    // Handle entity configuration types.
    if ($config_type && $config_type !== 'system.simple') {
      $entity_storage = $this->entityTypeManager->getStorage($config_type);
      foreach ($entity_storage->loadMultiple() as $entity) {
        $entity_id = $entity->id();
        if ($label = $entity->label()) {
          $names[$entity_id] = new TranslatableMarkup('@label (@id)', ['@label' => $label, '@id' => $entity_id]);
        }
        else {
          $names[$entity_id] = $entity_id;
        }
      }
    }
    // Handle simple configuration.
    elseif ($config_type == 'system.simple') {
      // Gather the configuration entity prefixes.
      $config_prefixes = array_map(function (EntityTypeInterface $definition) {
        return $definition->getConfigPrefix() . '.';
      }, $this->getConfigEntityTypeDefinitions(TRUE));

      // Get all configuration names.
      $names = $this->configStorage->listAll();
      $names = array_combine($names, $names);

      // Filter out any names that match a configuration entity prefix.
      foreach ($names as $config_name) {
        foreach ($config_prefixes as $config_prefix) {
          if (strpos($config_name, $config_prefix) === 0) {
            unset($names[$config_name]);
          }
        }
      }
    }

    return $names;
  }

  /**
   * Get the configuration items for a specified configuration name.
   *
   * @param string|null $config_type
   *   The configuration type.
   * @param string|null $config_name
   *   The configuration name.
   *
   * @return array
   *   The configuration items.
   */
  protected function getConfigItems($config_type = NULL, $config_name = NULL) {
    $config_items = [];

    if (!$config_name) {
      return $config_items;
    }

    // For simple configuration, use the configuration name. For configuration
    // entities, use a combination of the prefix and configuration name.
    if ($config_type == 'system.simple') {
      $name = $config_name;
    }
    else {
      $definition = $this->getConfigEntityTypeDefinitions()[$config_type];
      $name = $definition->getConfigPrefix() . '.' . $config_name;
    }

    $config_object = $this->configFactory->get($name);
    $config_array = $config_object->get();
    $config_items += $this->flattenConfigItemList($config_array);
    $config_items = array_combine($config_items, $config_items);

    return $config_items;
  }

  /**
   * Define the list of configuration types to exclude.
   *
   * @return array
   *   The configuration types to exclude.
   */
  protected function excludedConfigTypes() {
    $exclude = [
      'key',
      'key_config_override',
    ];
    return array_combine($exclude, $exclude);
  }

  /**
   * Recursively create a flat array of configuration items.
   *
   * @param array $config_array
   *   An array of configuration items.
   * @param string $prefix
   *   A prefix to add to nested items.
   * @param int $level
   *   The current level of nesting.
   *
   * @return array
   *   The flattened array of configuration items.
   */
  protected function flattenConfigItemList(array $config_array, $prefix = '', $level = 0) {
    $config_items = [];

    // Define items to ignore.
    $ignore = [
      'uuid',
      '_core',
    ];

    foreach ($config_array as $key => $value) {
      if (in_array($key, $ignore) && $level == 0) {
        continue;
      }

      if (is_array($value) && $level < 5) {
        $config_items = array_merge($config_items, $this->flattenConfigItemList($value, $prefix . $key . '.', $level + 1));
      }
      else {
        $config_items[] = $prefix . $key;
      }
    }

    return $config_items;
  }

}
