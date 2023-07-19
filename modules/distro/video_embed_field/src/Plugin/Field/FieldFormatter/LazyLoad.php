<?php

namespace Drupal\video_embed_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the thumbnail field formatter.
 *
 * @FieldFormatter(
 *   id = "video_embed_field_lazyload",
 *   label = @Translation("Video (lazy load)"),
 *   field_types = {
 *     "video_embed_field"
 *   }
 * )
 */
class LazyLoad extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The field formatter plugin instance for thumbnails.
   *
   * @var \Drupal\Core\Field\FormatterInterface
   */
  protected $thumbnailFormatter;

  /**
   * The field formatterp plguin instance for videos.
   *
   * @var \Drupal\Core\Field\FormatterInterface
   */
  protected $videoFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new instance of the plugin.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Field\FormatterInterface $thumbnail_formatter
   *   The field formatter for thumbnails.
   * @param \Drupal\Core\Field\FormatterInterface $video_formatter
   *   The field formatter for videos.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, $settings, $label, $view_mode, $third_party_settings, RendererInterface $renderer, FormatterInterface $thumbnail_formatter, FormatterInterface $video_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->thumbnailFormatter = $thumbnail_formatter;
    $this->videoFormatter = $video_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $formatter_manager = $container->get('plugin.manager.field.formatter');
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $formatter_manager->createInstance('video_embed_field_thumbnail', $configuration),
      $formatter_manager->createInstance('video_embed_field_video', $configuration)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $thumbnails = $this->thumbnailFormatter->viewElements($items, $langcode);
    $videos = $this->videoFormatter->viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      $itemThumb = [$thumbnails[$delta]];
      // Add a play button.
      $itemThumb[] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => [
          'class' => ['video-embed-field-lazy-play']
        ],
      ];
      $element[$delta] = [
        '#type' => 'container',
        '#attributes' => [
          'data-video-embed-field-lazy' => (string) $this->renderer->render($videos[$delta]),
          'class' => ['video-embed-field-lazy'],
        ],
        '#attached' => [
          'library' => [
            'video_embed_field/lazy-load',
          ],
        ],
        // Ensure the cache context from the video formatter which was rendered
        // early still exists in the renderable array for this formatter.
        '#cache' => [
          'contexts' => ['user.permissions'],
        ],
        'children' => $itemThumb,
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return Thumbnail::defaultSettings() + Video::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element += $this->thumbnailFormatter->settingsForm([], $form_state);
    $element += $this->videoFormatter->settingsForm([], $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Thumbnail that lazy loads video embed code.');
    $summary[] = implode(',', $this->videoFormatter->settingsSummary());
    $summary[] = implode(',', $this->thumbnailFormatter->settingsSummary());
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + $this->thumbnailFormatter->calculateDependencies() + $this->videoFormatter->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $parent = parent::onDependencyRemoval($dependencies);
    $thumbnail = $this->thumbnailFormatter->onDependencyRemoval($dependencies);
    $video = $this->videoFormatter->onDependencyRemoval($dependencies);
    $this->setSetting('image_style', $this->thumbnailFormatter->getSetting('image_style'));
    return $parent || $thumbnail || $video;
  }

}
