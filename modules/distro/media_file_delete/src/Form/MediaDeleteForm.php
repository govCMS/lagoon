<?php

namespace Drupal\media_file_delete\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for a media delete form.
 */
class MediaDeleteForm extends ContentEntityDeleteForm {

  /**
   * File usage resolver.
   *
   * @var \Drupal\media_file_delete\Usage\FileUsageResolverInterface
   */
  protected $usageResolver;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->usageResolver = $container->get('media_file_delete.file_usage_resolver.chained');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $media = $this->getEntity();
    assert($media instanceof MediaInterface);
    $file = $this->getFile($media);
    $build = parent::buildForm($form, $form_state);
    if (!$file) {
      return $build;
    }
    $file_owner = $file->getOwner();
    if (!$file->access('delete', $this->currentUser)) {
      return $build + [
        'cannot_delete' => [
          '#markup' => $this->t('The file attached to this media is owned by %name so will be retained.', ['%name' => $file_owner->getDisplayName()]),
        ],
      ];
    }
    $usages = $this->usageResolver->getFileUsages($file);
    if ($usages > 1) {
      return $build + [
        'cannot_delete' => [
          '#markup' => new PluralTranslatableMarkup($usages - 1, 'The file attached to this media is used in 1 other place and will be retained.', 'The file attached to this media is used in @count other places and will be retained.'),
        ],
      ];
    }
    return $build + [
      'also_delete_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Also delete the associated file?'),
        '#description' => $this->t('After deleting the media item, this will also remove the associated file %file from the file system.', [
          '%file' => $file->getFilename(),
        ]),
      ],
    ];
  }

  /**
   * Gets the file for the given media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media.
   *
   * @return \Drupal\file\FileInterface|null
   *   File.
   */
  protected function getFile(MediaInterface $media) : ?FileInterface {
    $field = $media->getSource()->getSourceFieldDefinition($media->bundle->entity);
    if (is_a($field->getItemDefinition()->getClass(), FileItem::class, TRUE) && $fid = $media->getSource()->getSourceFieldValue($media)) {
      return File::load($fid);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if (($file = $this->getFile($this->getEntity())) && $form_state->getValue('also_delete_file')) {
      $file->delete();
      $this->messenger()->addMessage($this->t('Deleted the associated file %name.', [
        '%name' => $file->getFilename(),
      ]));
    }
  }

}
