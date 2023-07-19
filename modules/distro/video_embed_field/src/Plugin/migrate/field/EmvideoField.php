<?php

namespace Drupal\video_embed_field\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Plugin to migrate from the Drupal 6 emfield module.
 *
 * @MigrateField(
 *   id = "emvideo",
 *   core = {6},
 *   source_module = "emfield",
 *   destination_module = "video_embed_field",
 * )
 */
class EmvideoField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    return 'video_embed_field';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'video_embed_field_video',
      'video' => 'video_embed_field_video',
      'thumbnail' => 'video_embed_field_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'emvideo_textfields' => 'video_embed_field_textfield',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'value' => 'embed',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
