<?php

namespace Drupal\entity_embed\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\editor\Entity\Editor;
use Drupal\embed\EmbedButtonInterface;
use Drupal\embed\EmbedCKEditorPluginBase;

/**
 * Defines the "drupalentity" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalentity",
 *   label = @Translation("Entity"),
 *   embed_type_id = "entity"
 * )
 */
class DrupalEntity extends EmbedCKEditorPluginBase implements CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   */
  protected function getButton(EmbedButtonInterface $embed_button) {
    $button = parent::getButton($embed_button);
    $button['entity_type'] = $embed_button->getTypeSetting('entity_type');
    return $button;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getModulePath('entity_embed') . '/js/plugins/drupalentity/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'DrupalEntity_dialogTitleAdd' => t('Insert entity'),
      'DrupalEntity_dialogTitleEdit' => t('Edit entity'),
      'DrupalEntity_buttons' => $this->getButtons(),
      'DrupalEntity_previewCsrfToken' => \Drupal::csrfToken()->get('X-Drupal-EmbedPreview-CSRF-Token'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
      $this->getModulePath('system') . '/css/components/hidden.module.css',
      $this->getModulePath('entity_embed') . '/css/entity_embed.editor.css',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Backwards compatible version for Drupal 9.2.
   */
  protected function getModulePath(string $module_name): string {
    // CKEditorPluginBase::getModulePath() was added in Drupal 9.3+.
    if (is_callable('parent::getModulePath')) {
      return parent::getModulePath($module_name);
    }

    return \Drupal::service('extension.list.module')->getPath($module_name);
  }

}
