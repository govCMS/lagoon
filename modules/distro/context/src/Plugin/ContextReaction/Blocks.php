<?php

namespace Drupal\context\Plugin\ContextReaction;

use Drupal\block\BlockRepositoryInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Block\BlockManager;
use Drupal\context\ContextInterface;
use Drupal\context\Form\AjaxFormTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\Exception\MissingValueContextException;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\context\Reaction\Blocks\BlockCollection;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Block\MainContentBlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Component\Plugin\DependentPluginInterface;

/**
 * Provides a content reaction.
 *
 * It will let you place blocks in the current themes regions.
 *
 * @ContextReaction(
 *   id = "blocks",
 *   label = @Translation("Blocks")
 * )
 */
class Blocks extends ContextReactionPluginBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface, DependentPluginInterface {

  use AjaxFormTrait;

  use PluginDependencyTrait {
    addDependency as addDependencyTrait;
  }

  /**
   * An array of blocks to be displayed with this reaction.
   *
   * @var array
   */
  protected $blocks = [];

  /**
   * Contains a temporary collection of blocks.
   *
   * @var \Drupal\context\Reaction\Blocks\BlockCollection
   */
  protected $blocksCollection;

  /**
   * The Drupal UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The handler of the available themes.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The Drupal context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    UuidInterface $uuid,
    ThemeManagerInterface $themeManager,
    ThemeHandlerInterface $themeHandler,
    ContextRepositoryInterface $contextRepository,
    ContextHandlerInterface $contextHandler,
    AccountInterface $account,
    BlockManager $blockManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);

    $this->uuid = $uuid;
    $this->themeManager = $themeManager;
    $this->themeHandler = $themeHandler;
    $this->contextRepository = $contextRepository;
    $this->contextHandler = $contextHandler;
    $this->account = $account;
    $this->blockManager = $blockManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('uuid'),
      $container->get('theme.manager'),
      $container->get('theme_handler'),
      $container->get('context.repository'),
      $container->get('context.handler'),
      $container->get('current_user'),
      $container->get('plugin.manager.block'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Executes the plugin.
   *
   * @param array $build
   *   The current build of the page.
   * @param string|null $title
   *   The page title.
   * @param string|null $main_content
   *   The main page content.
   *
   * @return array
   *   Blocks that will be built.
   */
  public function execute(array $build = [], $title = NULL, $main_content = NULL) {

    $cacheability = CacheableMetadata::createFromRenderArray($build);

    // Use the currently active theme to fetch blocks.
    $theme = $this->themeManager->getActiveTheme()->getName();

    $regions = $this->getBlocks()->getAllByRegion($theme);

    // Add each block to the page build.
    foreach ($regions as $region => $blocks) {

      /** @var $blocks BlockPluginInterface[] */
      foreach ($blocks as $block_id => $block) {
        $configuration = $block->getConfiguration();

        $block_placement_key = $this->blockShouldBePlacedUniquely($block)
          ? $block_id
          : $block->getConfiguration()['id'];

        if ($block instanceof MainContentBlockPluginInterface) {
          if (isset($build['content']['system_main'])) {
            unset($build['content']['system_main']);
          }
          $block->setMainContent($main_content);
        }

        // Same as Drupal\block\BlockAccessControlHandler::checkAccess().
        try {
          // Inject runtime contexts.
          // Must be before $block->access() to prevent ContextException.
          if ($block instanceof ContextAwarePluginInterface) {
            $contexts = $this->contextRepository->getRuntimeContexts($block->getContextMapping());
            $this->contextHandler->applyContextMapping($block, $contexts);
          }
          // Make sure the user is allowed to view the block.
          $access = $block->access($this->account, TRUE);
        }
        catch (MissingValueContextException $e) {
          // The contexts exist but have no value. Deny access without
          // disabling caching.
          $access = AccessResult::forbidden();
        }
        catch (ContextException $e) {
          // If any context is missing then we might be missing cacheable
          // metadata, and don't know based on what conditions the block is
          // accessible or not. Make sure the result cannot be cached.
          $access = AccessResult::forbidden()->setCacheMaxAge(0);
        }

        $cacheability->addCacheableDependency($access);

        // If the user is not allowed then do not render the block.
        if (!$access->isAllowed()) {
          continue;
        }

        if ($block instanceof TitleBlockPluginInterface) {
          if (isset($build['content']['messages'])) {
            unset($build['content']['messages']);
          }
          $block->setTitle($title);
        }

        $context_entity = $this->entityTypeManager
          ->getStorage('context')
          ->load($configuration['context_id']);

        // Create the render array for the block as a whole.
        // @see template_preprocess_block().
        $block_build = [
          '#theme' => 'block',
          // Must be defined to avoid array merge error in preRender().
          '#attributes' => [],
          '#configuration' => $configuration,
          '#plugin_id' => $block->getPluginId(),
          '#base_plugin_id' => $block->getBaseId(),
          '#derivative_plugin_id' => $block->getDerivativeId(),
          '#id' => $block->getConfiguration()['custom_id'],
          '#block_plugin' => $block,
          // Add a block entity with the configuration of the block plugin so
          // modules depending on the block property in e.g.
          // hook_block_view_alter will still work.
          '#block' => Block::create($this->blocks[$block_id] + ['plugin' => $block->getPluginId()]),
          '#pre_render' => [[$this, 'preRenderBlock']],
          '#cache' => [
            'keys' => [
              'context_blocks_reaction',
              $configuration['context_id'],
              'block',
              $block_placement_key,
            ],
            'tags' => Cache::mergeTags($block->getCacheTags(), !empty($context_entity) ? $context_entity->getCacheTags() : []),
            'contexts' => $block->getCacheContexts(),
            'max-age' => $block->getCacheMaxAge(),
          ],
        ];

        // Add additional contextual link, for editing block configuration.
        $block_build['#contextual_links']['context_block'] = [
          'route_parameters' => [
            'context' => $configuration['context_id'],
            'reaction_id' => 'blocks',
            'block_id' => $block->getConfiguration()['uuid'],
          ],
        ];

        if (array_key_exists('weight', $configuration)) {
          $block_build['#weight'] = $configuration['weight'];
        }

        // Invoke block_view_alter().
        // If an alter hook wants to modify the block contents, it can append
        // another #pre_render hook.
        \Drupal::moduleHandler()->alter(['block_view', 'block_view_' . $block->getBaseId()], $block_build, $block);

        // Allow altering of cacheability metadata or setting #create_placeholder.
        \Drupal::moduleHandler()->alter(['block_build', "block_build_" . $block->getBaseId()], $block_build, $block);

        $build[$region][$block_placement_key] = $block_build;

        // After merging with blocks from Block layout, we want to sort all of
        // them again.
        $build[$region]['#sorted'] = FALSE;

        // The main content block cannot be cached: it is a placeholder for the
        // render array returned by the controller. It should be rendered as-is,
        // with other placed blocks "decorating" it. Analogous reasoning for the
        // title block.
        if ($block instanceof MainContentBlockPluginInterface || $block instanceof TitleBlockPluginInterface) {
          unset($build[$region][$block_placement_key]['#cache']['keys']);
        }

        $cacheability->addCacheableDependency($block);
      }
    }

    $cacheability->applyTo($build);

    return $build;
  }

  /**
   * Renders the content using the provided block plugin.
   *
   * @param array $build
   *   The block to be rendered.
   *
   * @return array
   *   The block already rendered.
   */
  public function preRenderBlock(array $build) {

    $content = $build['#block_plugin']->build();

    unset($build['#block_plugin']);

    // Abort rendering: render as the empty string and ensure this block is
    // render cached, so we can avoid the work of having to repeatedly
    // determine whether the block is empty. E.g. modifying or adding entities
    // could cause the block to no longer be empty.
    if (is_null($content) || Element::isEmpty($content)) {
      $build = [
        '#markup' => '',
        '#cache' => $build['#cache'],
      ];

      // If $content is not empty, then it contains cacheability metadata, and
      // we must merge it with the existing cacheability metadata. This allows
      // blocks to be empty, yet still bubble cacheability metadata, to indicate
      // why they are empty.
      if (!empty($content)) {
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromRenderArray($content))
          ->applyTo($build);
      }
    }
    else {
      foreach (['#attributes', '#contextual_links'] as $property) {
        if (isset($content[$property])) {
          $build[$property] += $content[$property];
          unset($content[$property]);
        }
      }
      $block_configuration = $build['#configuration'];
      // Merge attributes from context.
      // @see #3150394 and #2979536.
      $existing_attributes = isset($build['#attributes']) ? $build['#attributes'] : [];

      // Merge existing attributes from block with class(es) configured
      // in Context.
      if (isset($block_configuration['css_class']) && '' !== $block_configuration['css_class']) {
        $new_attributes = [
          'class' => [$block_configuration['css_class']],
        ];
        $existing_attributes = array_merge_recursive($existing_attributes, $new_attributes);
      }
      $build['#attributes'] = $existing_attributes;
      $build['content'] = $content;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'blocks' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();

    if (isset($configuration['blocks'])) {
      $this->blocks = $configuration['blocks'];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'blocks' => $this->getBlocks()->getConfiguration(),
    ] + parent::getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Lets you add blocks to the selected themes regions');
  }

  /**
   * Get all blocks as a collection.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface[]|BlockCollection
   *   The collection of blocks.
   */
  public function getBlocks() {
    if (!$this->blocksCollection) {
      $this->blocksCollection = new BlockCollection($this->blockManager, $this->blocks);
    }

    return $this->blocksCollection;
  }

  /**
   * Get a block by id.
   *
   * @param string $blockId
   *   The ID of the block to get.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The specified block plugin.
   */
  public function getBlock($blockId) {
    return $this->getBlocks()->get($blockId);
  }

  /**
   * Add a new block.
   *
   * @param array $configuration
   *   The configuration from the block.
   *
   * @return string
   *   The uuid from the block.
   */
  public function addBlock(array $configuration) {
    $configuration['uuid'] = $this->uuid->generate();

    $this->getBlocks()->addInstanceId($configuration['uuid'], $configuration);

    return $configuration['uuid'];
  }

  /**
   * Update an existing blocks configuration.
   *
   * @param string $blockId
   *   The ID of the block to update.
   * @param array $configuration
   *   The updated configuration for the block.
   *
   * @return Drupal\context\Plugin\ContextReaction
   *   This object.
   */
  public function updateBlock($blockId, array $configuration) {
    $existingConfiguration = $this->getBlock($blockId)->getConfiguration();

    $this->getBlocks()->setInstanceConfiguration($blockId, $configuration + $existingConfiguration);

    return $this;
  }

  /**
   * Remove block.
   *
   * @param string $blockId
   *   Block id to removed.
   *
   * @return Drupal\context\Plugin\ContextReaction
   *   This object.
   */
  public function removeBlock($blockId) {
    $this->getBlocks()->removeInstanceId($blockId);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, ContextInterface $context = NULL) {
    $form['#attached']['library'][] = 'block/drupal.block';

    $themes = $this->themeHandler->listInfo();

    $default_theme = $this->themeHandler->getDefault();

    // Select list for changing themes.
    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#options' => [],
      '#description' => $this->t('Select the theme you want to display regions for.'),
      '#default_value' => $form_state->getValue('theme', $default_theme),
      '#ajax' => [
        'url' => Url::fromRoute('context.reaction.blocks.regions', [
          'context' => $context->id(),
        ]),
      ],
    ];

    // Add each theme to the theme select.
    foreach ($themes as $theme_id => $theme) {
      if ($theme_id === $default_theme) {
        $form['theme']['#options'][$theme_id] = $this->t('%theme (Default)', [
          '%theme' => $theme->info['name'],
        ]);
      }
      else {
        $form['theme']['#options'][$theme_id] = $theme->info['name'];
      }
    }

    $form['blocks'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'context-reaction-blocks-container',
      ],
    ];

    $form['blocks']['include_default_blocks'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include blocks from Block layout'),
      '#description' => $this->t('if checked, all blocks from default Block layout will also be included in page build.'),
      '#weight' => -10,
      '#default_value' => isset($this->getConfiguration()['include_default_blocks']) ? $this->getConfiguration()['include_default_blocks'] : FALSE,
    ];

    $form['blocks']['block_add'] = [
      '#type' => 'link',
      '#title' => $this->t('Place block'),
      '#attributes' => [
        'id' => 'context-reaction-blocks-region-add',
      ] + $this->getAjaxButtonAttributes(),
      '#url' => Url::fromRoute('context.reaction.blocks.library', [
        'context' => $context->id(),
        'reaction_id' => $this->getPluginId(),
      ], [
        'query' => [
          'theme' => $form_state->getValue('theme', $default_theme),
        ],
      ]),
    ];

    $form['blocks']['blocks'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block'),
        $this->t('Category'),
        $this->t('Unique'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No regions available to place blocks in.'),
      '#attributes' => [
        'id' => 'blocks',
      ],
    ];

    // If a theme has been selected use that to get the regions otherwise use
    // the default theme.
    $theme = $form_state->getValue('theme', $default_theme);

    // Get all blocks by their regions.
    $blocks = $this->getBlocks()->getAllByRegion($theme);

    // Get regions of the selected theme.
    $regions = $this->getSystemRegionList($theme);

    // Add each region.
    foreach ($regions as $region => $title) {

      // Add the tabledrag details for this region.
      $form['blocks']['blocks']['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region,
        'hidden' => FALSE,
      ];

      $form['blocks']['blocks']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region,
      ];

      // Add the theme region.
      $form['blocks']['blocks']['region-' . $region] = [
        '#attributes' => [
          'class' => ['region-title'],
        ],
        'title' => [
          '#markup' => $title,
          '#wrapper_attributes' => [
            'colspan' => 6,
          ],
        ],
      ];

      $regionEmptyClass = empty($blocks[$region])
        ? 'region-empty'
        : 'region-populated';

      $form['blocks']['blocks']['region-' . $region . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region . '-message',
            $regionEmptyClass,
          ],
        ],
        'message' => [
          '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
          '#wrapper_attributes' => [
            'colspan' => 6,
          ],
        ],
      ];

      // Add each block specified for the region if there are any.
      if (isset($blocks[$region])) {
        /** @var \Drupal\Core\Block\BlockPluginInterface $block */
        foreach ($blocks[$region] as $block_id => $block) {
          $configuration = $block->getConfiguration();

          $operations = [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('context.reaction.blocks.block_edit', [
                'context' => $context->id(),
                'reaction_id' => $this->getPluginId(),
                'block_id' => $block_id,
              ], [
                'query' => [
                  'theme' => $theme,
                ],
              ]),
              'attributes' => $this->getAjaxAttributes(),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('context.reaction.blocks.block_delete', [
                'context' => $context->id(),
                'block_id' => $block_id,
              ]),
              'attributes' => $this->getAjaxAttributes(),
            ],
          ];

          $form['blocks']['blocks'][$block_id] = [
            '#attributes' => [
              'class' => ['draggable'],
            ],
            'label' => [
              '#markup' => $block->label(),
            ],
            'category' => [
              '#markup' => $block->getPluginDefinition()['category'],
            ],
            'unique' => [
              '#markup' => $this->blockShouldBePlacedUniquely($block) ? $this->t('Yes') : $this->t('No'),
            ],
            'region' => [
              '#type' => 'select',
              '#title' => $this->t('Region for @block block', ['@block' => $block->label()]),
              '#title_display' => 'invisible',
              '#default_value' => $region,
              '#options' => $regions,
              '#attributes' => [
                'class' => ['block-region-select', 'block-region-' . $region],
              ],
            ],
            'weight' => [
              '#type' => 'weight',
              '#default_value' => isset($configuration['weight']) ? $configuration['weight'] : 0,
              '#title' => $this->t('Weight for @block block', ['@block' => $block->label()]),
              '#title_display' => 'invisible',
              '#attributes' => [
                'class' => ['block-weight', 'block-weight-' . $region],
              ],
            ],
            'operations' => [
              '#type' => 'operations',
              '#links' => $operations,
            ],
          ];
        }
      }
    }

    return $form;
  }

  /**
   * Check to see if the block should be uniquely placed.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block plugin.
   *
   * @return bool
   *   TRUE if block should be placed uniquely, FALSE if not.
   */
  private function blockShouldBePlacedUniquely(BlockPluginInterface $block) {
    $configuration = $block->getConfiguration();
    return (isset($configuration['unique']) && $configuration['unique']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $blocks = $form_state->getValue(['blocks', 'blocks'], []);

    // Save configuration for including default blocks.
    $config = $this->getConfiguration();
    $config['include_default_blocks'] = $form_state->getValue(['blocks', 'include_default_blocks'], FALSE);
    $this->setConfiguration($config);

    if (is_array($blocks)) {
      foreach ($blocks as $block_id => $configuration) {
        $block = $this->getBlock($block_id);
        $configuration += $block->getConfiguration();

        $block_state = (new FormState())->setValues($configuration);
        $block->submitConfigurationForm($form, $block_state);

        // If the block is context aware then add context mapping to the block.
        if ($block instanceof ContextAwarePluginInterface) {
          $block->setContextMapping($block_state->getValue('context_mapping', []));
        }

        $this->updateBlock($block_id, $block_state->getValues());
      }
    }
  }

  /**
   * Should reaction include default blocks from Block layout.
   *
   * @return bool
   *   TRUE if default blocks will be included, FALSE if not.
   */
  public function includeDefaultBlocks() {
    $config = $this->getConfiguration();
    return isset($config['include_default_blocks']) ? $config['include_default_blocks'] : FALSE;
  }

  /**
   * Wraps system_region_list().
   *
   * @param string $theme
   *   The theme to get a list of regions for.
   * @param string $show
   *   What type of regions that should be returned, defaults to all regions.
   *
   * @return array
   *   An array of available regions from a specified theme.
   *
   * @todo This could be moved to a service since we use it in a couple of places.
   */
  protected function getSystemRegionList($theme, $show = BlockRepositoryInterface::REGIONS_ALL) {
    return system_region_list($theme, $show);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();
    foreach ($this->getBlocks() as $instance) {
      $this->calculatePluginDependencies($instance);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderBlock'];
  }

}
