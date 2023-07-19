<?php

namespace Drupal\video_embed_field\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Plugin to migrate from the Drupal 7 video_embed_field module.
 *
 * @MigrateField(
 *   id = "video_embed_field",
 *   core = {7},
 *   source_module = "video_embed_field",
 *   destination_module = "video_embed_field",
 * )
 */
class VideoEmbedField extends FieldPluginBase {

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
      'video_embed_field' => 'video_embed_field_video',
      'video_embed_field_thumbnail' => 'video_embed_field_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'video_embed_field_video' => 'video_embed_field_textfield',
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
        'value' => 'video_url',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
