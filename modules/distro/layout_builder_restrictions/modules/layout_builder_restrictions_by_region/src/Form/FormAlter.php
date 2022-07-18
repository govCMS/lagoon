<?php

namespace Drupal\layout_builder_restrictions_by_region\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder_restrictions\Traits\PluginHelperTrait;
use Drupal\layout_builder_restrictions_by_region\Traits\LayoutBuilderRestrictionsByRegionHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Supplement form UI to add setting for which blocks & layouts are available.
 */
class FormAlter implements ContainerInjectionInterface {

  use PluginHelperTrait;
  use LayoutBuilderRestrictionsByRegionHelperTrait;
  use DependencySerializationTrait;

  /**
   * The section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The layout manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * A service for generating UUIDs.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * Creates a private temporary storage for a collection.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * FormAlter constructor.
   *
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Block\LayoutPluginManagerInterface $layout_manager
   *   The layout plugin manager.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   A service for generating UUIDs.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $private_temp_store_factory
   *   Creates a private temporary storage for a collection.
   */
  public function __construct(SectionStorageManagerInterface $section_storage_manager, BlockManagerInterface $block_manager, LayoutPluginManagerInterface $layout_manager, ContextHandlerInterface $context_handler, UuidInterface $uuid, PrivateTempStoreFactory $private_temp_store_factory) {
    $this->sectionStorageManager = $section_storage_manager;
    $this->blockManager = $block_manager;
    $this->layoutManager = $layout_manager;
    $this->contextHandler = $context_handler;
    $this->uuid = $uuid;
    $this->privateTempStoreFactory = $private_temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.layout_builder.section_storage'),
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.core.layout'),
      $container->get('context.handler'),
      $container->get('uuid'),
      $container->get('tempstore.private')
    );
  }

  /**
   * The actual form elements.
   */
  public function alterEntityViewDisplayForm(&$form, FormStateInterface &$form_state, $form_id) {
    // Create a unique ID for this form build and store it in a hidden
    // element on the rendered form. This will be used to retrieve data
    // from tempStore.
    $user_input = $form_state->getUserInput();
    if (!isset($user_input['static_id'])) {
      $static_id = $this->uuid->generate();

      $form['static_id'] = [
        '#type' => 'hidden',
        '#value' => $static_id,
      ];
    }
    else {
      $static_id = $user_input['static_id'];
    }

    $display = $form_state->getFormObject()->getEntity();
    $is_enabled = $display->isLayoutBuilderEnabled();
    if ($is_enabled) {
      $form['layout']['layout_builder_restrictions']['messages'] = [
        '#markup' => '<div id="layout-builder-restrictions-messages" class="hidden"></div>',
      ];

      $form['#entity_builders'][] = [$this, 'entityFormEntityBuild'];
      // Layout settings.
      $third_party_settings = $display->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction_by_region', []);
      $allowed_layouts = (isset($third_party_settings['allowed_layouts'])) ? $third_party_settings['allowed_layouts'] : [];
      $layout_form = [
        '#type' => 'details',
        '#title' => $this->t('Layouts available for sections'),
        '#parents' => ['layout_builder_restrictions', 'allowed_layouts'],
        '#states' => [
          'disabled' => [
            ':input[name="layout[enabled]"]' => ['checked' => FALSE],
          ],
          'invisible' => [
            ':input[name="layout[enabled]"]' => ['checked' => FALSE],
          ],
        ],
      ];
      $layout_form['layout_restriction'] = [
        '#type' => 'radios',
        '#options' => [
          "all" => $this->t('Allow all existing & new layouts.'),
          "restricted" => $this->t('Allow only specific layouts:'),
        ],
        '#default_value' => !empty($allowed_layouts) ? "restricted" : "all",
      ];

      $entity_view_display_id = $display->get('id');
      $definitions = $this->getLayoutDefinitions();
      foreach ($definitions as $section => $definition) {
        $enabled = FALSE;
        if (!empty($allowed_layouts) && in_array($section, $allowed_layouts)) {
          $enabled = TRUE;
        }
        $layout_form['layouts'][$section] = [
          '#type' => 'checkbox',
          '#default_value' => $enabled,
          '#description' => [
            $definition->getIcon(60, 80, 1, 3),
            [
              '#type' => 'container',
              '#children' => $definition->getLabel() . ' (' . $section . ')',
            ],
          ],
          '#attributes' => [
            'data-layout-plugin' => [
              $section,
            ],
          ],
          '#states' => [
            'invisible' => [
              ':input[name="layout_builder_restrictions[allowed_layouts][layout_restriction]"]' => ['value' => "all"],
            ],
          ],
        ];
      }
      $form['layout']['layout_builder_restrictions']['allowed_layouts'] = $layout_form;

      // Block settings.
      $layout_definitions = $definitions;

      foreach ($layout_definitions as $section => $definition) {
        $regions = $definition->getRegions();
        $regions['all_regions'] = [
          'label' => $this->t('All regions'),
        ];

        $form['layout'][$section] = [
          '#type' => 'details',
          '#title' => $this->t('Blocks available for the <em>@layout_label</em> layout', ['@layout_label' => $definition->getLabel()]),
          '#parents' => [
            'layout_builder_restrictions',
            'allowed_blocks_by_layout',
            $section,
          ],
          '#attributes' => [
            'data-layout-plugin' => $section,
          ],
          '#states' => [
            'disabled' => [
              [':input[name="layout[enabled]"]' => ['checked' => FALSE]],
              'or',
              ['#edit-layout-builder-restrictions-allowed-layouts :input[data-layout-plugin="' . $section . '"]' => ['checked' => FALSE]],
            ],
            'invisible' => [
              [':input[name="layout[enabled]"]' => ['checked' => FALSE]],
              'or',
              ['#edit-layout-builder-restrictions-allowed-layouts :input[data-layout-plugin="' . $section . '"]' => ['checked' => FALSE]],
            ],
          ],
        ];
        $default_restriction_behavior = 'all';
        if (isset($third_party_settings['whitelisted_blocks'][$section]) && !isset($third_party_settings['whitelisted_blocks'][$section]['all_regions'])) {
          $default_restriction_behavior = 'per-region';
        }
        if (isset($third_party_settings['blacklisted_blocks'][$section]) && !isset($third_party_settings['blacklisted_blocks'][$section]['all_regions'])) {
          $default_restriction_behavior = 'per-region';
        }
        if (isset($third_party_settings['restricted_categories'][$section]) && !isset($third_party_settings['restricted_categories'][$section]['all_regions'])) {
          $default_restriction_behavior = 'per-region';
        }
        $form['layout'][$section]['restriction_behavior'] = [
          '#type' => 'radios',
          '#options' => [
            "all" => $this->t('Apply block restrictions to all regions in layout'),
            "per-region" => $this->t('Apply block restrictions on a region-by-region basis'),
          ],
          '#attributes' => [
            'class' => [
              'restriction-type',
            ],
            'data-layout-plugin' => $section,
          ],
          '#default_value' => $default_restriction_behavior,
        ];

        $form['layout'][$section]['table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Region'),
            $this->t('Status'),
            $this->t('Operations'),
          ],
          '#attributes' => [
            'data-layout' => $section,
          ],
        ];

        foreach ($regions as $region_id => $region) {
          $form['layout'][$section]['table']['#rows'][$region_id] = [
            'data-region' => $region_id,
            'data' => [
              'region_label' => [
                'class' => [
                  'region-label',
                ],
                'data' => [
                  '#markup' => $region['label']->render(),
                ],
              ],
              'status' => [
                'class' => [
                  'restriction-status',
                ],
                'id' => 'restriction-status--' . $section . '--' . $region_id,
                'data' => [
                  '#markup' => '<span class="data">' . $this->RegionRestrictionStatusString($section, $region_id, $static_id, $entity_view_display_id) . '</span>',
                ],
              ],
              'operations' => [
                'class' => [
                  'operations',
                ],
                'data' => [
                  '#type' => 'dropbutton',
                  '#links' => [
                    'manage' => [
                      'title' => $this->t('Manage allowed blocks'),
                      'url' => Url::fromRoute("layout_builder_restrictions_by_region.{$form['#entity_type']}_allowed_blocks", [
                        'static_id' => $static_id,
                        'entity_view_display_id' => $entity_view_display_id,
                        'layout_plugin' => $section,
                        'region_id' => $region_id,
                      ]),
                      'attributes' => [
                        'class' => [
                          'use-ajax',
                        ],
                        'data-dialog-type' => 'modal',
                        'data-dialog-options' => Json::encode(['width' => 800]),
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ];
        }
      }

      // Add certain variables as form state temp value for later use.
      $form_state->setTemporaryValue('static_id', $static_id);

      $form['#attached']['library'][] = 'layout_builder_restrictions_by_region/display_mode_form';
    }
  }

  /**
   * Save allowed blocks & layouts for the given entity view mode.
   */
  public function entityFormEntityBuild($entity_type_id, LayoutEntityDisplayInterface $display, &$form, FormStateInterface &$form_state) {
    $static_id = $form_state->getTemporaryValue('static_id');
    // @todo change naming to avoid color-based metaphor.
    $restriction_types = ['whitelisted', 'blacklisted'];

    // Set allowed layouts.
    $layout_restriction = $form_state->getValue([
      'layout_builder_restrictions',
      'allowed_layouts',
      'layout_restriction',
    ]);
    $allowed_layouts = [];
    if ($layout_restriction == 'restricted') {
      $allowed_layouts = array_keys(array_filter($form_state->getValue([
        'layout_builder_restrictions',
        'allowed_layouts',
        'layouts',
      ])));
    }
    $third_party_settings = $display->getThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction_by_region');
    $third_party_settings['allowed_layouts'] = $allowed_layouts;

    // Set allowed blocks.
    $tempstore = $this->privateTempStoreFactory;
    $store = $tempstore->get('layout_builder_restrictions_by_region');

    $layout_definitions = $this->getLayoutDefinitions();

    foreach ($allowed_layouts as $section) {

      $layout_definition = $layout_definitions[$section];

      $regions = $layout_definition->getRegions();

      $regions['all_regions'] = [
        'label' => $this->t('All regions'),
      ];

      // Set allowed layouts.
      $layout_behavior = $form_state->getValue([
        'layout_builder_restrictions',
        'allowed_blocks_by_layout',
        $section,
      ]);

      // Handle scenario where all_regions configuration has not been modified
      // and needs to be preserved.
      $all_regions_temp = $store->get($static_id . ':' . $section . ':all_regions');
      if ($layout_behavior['restriction_behavior'] == 'all' && is_null($all_regions_temp)) {
        if (isset($third_party_settings['whitelisted_blocks'][$section]['all_regions'])) {
          $all_regions_whitelisted = $third_party_settings['whitelisted_blocks'][$section]['all_regions'];
        }
        if (isset($third_party_settings['blacklisted_blocks'][$section]['all_regions'])) {
          $all_regions_blacklisted = $third_party_settings['blacklisted_blocks'][$section]['all_regions'];
        }
        if (isset($third_party_settings['restricted_categories'][$section]['all_regions'])) {
          $all_regions_restricted_categories = $third_party_settings['restricted_categories'][$section]['all_regions'];
        }
        foreach ($restriction_types as $logic_type) {
          unset($third_party_settings[$logic_type . '_blocks'][$section]);
        }
        unset($third_party_settings['restricted_categories'][$section]);
        if (isset($all_regions_whitelisted)) {
          $third_party_settings['whitelisted_blocks'][$section]['all_regions'] = $all_regions_whitelisted;
        }
        if (isset($all_regions_blacklisted)) {
          $third_party_settings['blacklisted_blocks'][$section]['all_regions'] = $all_regions_blacklisted;
        }
        if (isset($all_regions_restricted_categories)) {
          $third_party_settings['restricted_categories'][$section]['all_regions'] = $all_regions_restricted_categories;
        }
      }
      else {
        // Unset 'all_regions'. This will be readded if there is tempstore data.
        foreach ($restriction_types as $logic_type) {
          unset($third_party_settings[$logic_type . '_blocks'][$section]);
        }
        unset($third_party_settings['restricted_categories'][$section]);
        foreach ($regions as $region_id => $region) {
          $categories = $store->get($static_id . ':' . $section . ':' . $region_id);
          if (!is_null($categories)) {
            foreach ($categories as $category => $settings) {
              $restriction_type = $settings['restriction_type'];
              // Category is restricted.
              if ($restriction_type == 'restrict_all') {
                $third_party_settings['restricted_categories'][$section][$region_id][] = $category;
              }
              elseif (in_array($restriction_type, $restriction_types)) {
                if (empty($settings['restrictions'])) {
                  $third_party_settings[$restriction_type . '_blocks'][$section][$region_id][$category] = [];
                }
                else {
                  foreach ($settings['restrictions'] as $block_id => $block_setting) {
                    $third_party_settings[$restriction_type . '_blocks'][$section][$region_id][$category][] = $block_id;
                  }
                }
              }
            }
          }
        }
      }
    }
    // Ensure data is saved in consistent alpha order by region.
    foreach ($restriction_types as $logic_type) {
      if (isset($third_party_settings[$logic_type . '_blocks'])) {
        foreach ($third_party_settings[$logic_type . '_blocks'] as $section => $regions) {
          ksort($regions);
          $third_party_settings[$logic_type . '_blocks'][$section] = $regions;
        }
      }
      if (isset($third_party_settings[$logic_type . '_blocks'])) {
        // Ensure data is saved in alpha order by layout.
        ksort($third_party_settings[$logic_type . '_blocks']);
      }
    }
    $display->setThirdPartySetting('layout_builder_restrictions', 'entity_view_mode_restriction_by_region', $third_party_settings);
  }

}
