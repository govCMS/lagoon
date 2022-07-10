<?php

namespace Drupal\context\Plugin\DisplayVariant;

use Drupal\context\ContextManager;
use Drupal\Core\Display\VariantBase;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Display\VariantManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page display variant that decorates the main content with blocks.
 *
 * @see \Drupal\Core\Block\MainContentBlockPluginInterface
 * @see \Drupal\Core\Block\MessagesBlockPluginInterface
 *
 * @PageDisplayVariant(
 *   id = "context_block_page",
 *   admin_label = @Translation("Page with blocks")
 * )
 */
class ContextBlockPageVariant extends VariantBase implements PageVariantInterface, ContainerFactoryPluginInterface {

  /**
   * The Context module context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent = [];

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * The display variant plugin manager.
   *
   * @var \Drupal\Core\Display\VariantManager
   */
  protected $displayVariant;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a new ContextBlockPageVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\context\ContextManager $contextManager
   *   The context module manager.
   * @param \Drupal\Core\Display\VariantManager $displayVariant
   *   The variant manager.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The Drupal theme manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextManager $contextManager, VariantManager $displayVariant, ThemeManagerInterface $themeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contextManager = $contextManager;
    $this->displayVariant = $displayVariant;
    $this->themeManager = $themeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.manager'),
      $container->get('plugin.manager.display_variant'),
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    $this->mainContent = $main_content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#cache' => [
        'tags' => ['context_block_page', $this->getPluginId()],
      ],
    ];

    // Place main content block, it will be removed by the reactions if a main
    // content block has been manually placed.
    $build['content']['system_main'] = $this->mainContent;

    // Execute each block reaction and let them modify the page build.
    foreach ($this->contextManager->getActiveReactions('blocks') as $reaction) {
      $build = $reaction->execute($build, $this->title, $this->mainContent);
    }

    // Execute each block reaction and check if default block should be included
    // in page build.
    foreach ($this->contextManager->getActiveReactions('blocks') as $reaction) {
      if ($reaction->includeDefaultBlocks()) {
        $build_block_layout = $this->getBuildFromBlockLayout();
        // Only merge at block level, not underneath,
        // else, unexpected consequences will arise.
        $regions = $this->themeManager->getActiveTheme()->getRegions();
        foreach ($regions as $region_key) {
          if (empty($build[$region_key])) {
            $build[$region_key] = [];
          }
          if (empty($build_block_layout[$region_key])) {
            $build_block_layout[$region_key] = [];
          }
          $build[$region_key] += $build_block_layout[$region_key];

        }
        // Merge bubbleable cache data now.
        BubbleableMetadata::createFromRenderArray($build)
          ->merge(BubbleableMetadata::createFromRenderArray($build_block_layout))
          ->applyTo($build);

        // Remove main content as it can added from core block layout or context
        // without the other knowing.
        foreach (Element::children($build) as $region_key) {
          foreach ($build[$region_key] as $blockId) {
            if (isset($blockId['#plugin_id']) && $blockId['#plugin_id'] == 'system_main_block') {
              unset($build['content']['system_main']);
              break;
            }
          }
        }
        // Remove systems messages block if it's added via context.
        foreach ($build as $block) {
          if (array_key_exists('system_messages_block', $block)) {
            unset($build['content']['messages']);
            break;
          }
        }
        return $build;
      }
    }
    return $build;
  }

  /**
   * Get build from Block layout.
   */
  private function getBuildFromBlockLayout() {
    $display_variant = $this->displayVariant->createInstance('block_page', $this->displayVariant->getDefinition('block_page'));
    $display_variant->setTitle($this->title);
    $display_variant->setMainContent($this->mainContent);

    return $display_variant->build();
  }

}
