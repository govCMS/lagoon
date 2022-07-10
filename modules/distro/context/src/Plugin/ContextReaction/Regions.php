<?php

namespace Drupal\context\Plugin\ContextReaction;

use Drupal\block\BlockRepositoryInterface;
use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a content reaction that will let you disable regions.
 *
 * @ContextReaction(
 *   id = "regions",
 *   label = @Translation("Regions")
 * )
 */
class Regions extends ContextReactionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * An array of regions to be disabled with this reaction.
   *
   * @var array
   */
  protected $regions = [];

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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    ThemeManagerInterface $themeManager,
    ThemeHandlerInterface $themeHandler
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->themeManager = $themeManager;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('theme.manager'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Lets you remove regions from selected theme.');
  }

  /**
   * Executes the plugin.
   */
  public function execute() {
    // TODO: Implement execute() method.
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $themes = $this->themeHandler->listInfo();
    $default_theme = $this->themeHandler->getDefault();

    // Build configuration form for each installed theme.
    foreach ($themes as $theme_id => $theme) {
      if ($theme_id == $default_theme) {
        $title = $this->t('Disable Regions in %theme (Default)', [
          '%theme' => $theme->info['name'],
        ]);
      }
      else {
        $title = $this->t('Disable Regions in %theme', [
          '%theme' => $theme->info['name'],
        ]);
      }

      $form[$theme_id] = [
        '#type' => 'details',
        '#title' => $title,
        '#weight' => 5,
        '#open' => FALSE,
      ];

      // Get regions of the theme.
      $regions = $this->getSystemRegionList($theme_id);

      // Get disabled regions.
      $disabled_regions = $this->getDisabledRegions();

      $form[$theme_id]['regions'] = [
        '#type' => 'checkboxes',
        '#options' => $regions,
        '#title' => $this->t('Disable the following'),
        '#default_value' => isset($disabled_regions[$theme_id]) ? $disabled_regions[$theme_id] : [],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $themes = $form_state->getValues();
    if (is_array($themes)) {
      foreach ($themes as $theme_name => $region) {
        $disabled_regions = array_keys(array_filter($region['regions']));
        if (!empty($disabled_regions)) {
          $configuration['regions'][$theme_name] = $disabled_regions;
          $configuration += $this->getConfiguration();
        }
        else {
          $configuration['regions'][$theme_name] = [];
          $configuration += $this->getConfiguration();
        }
        $this->setConfiguration($configuration);
      }
    }
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
   *   The list of available regions from a specified theme.
   *
   * @todo This could be moved to a service since we use it in a couple of places.
   */
  protected function getSystemRegionList($theme, $show = BlockRepositoryInterface::REGIONS_ALL) {
    return system_region_list($theme, $show);
  }

  /**
   * Get disabled regions.
   */
  protected function getDisabledRegions() {
    $configurations = $this->getConfiguration();
    return isset($configurations['regions']) ? $configurations['regions'] : [];
  }

}
