<?php

namespace Drupal\media_file_delete\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media\MediaInterface;
use Drupal\media_file_delete\Usage\FileUsageResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for a media delete multiple form.
 */
class MediaDeleteMultipleForm extends DeleteMultipleForm {

  /**
   * File usage resolver.
   *
   * @var \Drupal\media_file_delete\Usage\FileUsageResolverInterface
   */
  protected $usageResolver;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory, MessengerInterface $messenger, FileUsageResolverInterface $file_usage_resolver) {
    parent::__construct($current_user, $entity_type_manager, $temp_store_factory, $messenger);
    $this->usageResolver = $file_usage_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('messenger'),
      $container->get('media_file_delete.file_usage_resolver.chained')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $build = parent::buildForm($form, $form_state, $entity_type_id);
    return $build + [
      'also_delete_file' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Also delete the associated files?'),
        '#description' => $this->t('After deleting the media items, this will also remove the associated files from the file system.'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_deleted_count = 0;
    $file_inacessible_count = 0;
    $file_in_use_count = 0;
    $storage = $this->entityTypeManager->getStorage($this->entityTypeId);
    $entities = $storage->loadMultiple(array_keys($this->selection));

    parent::submitForm($form, $form_state);

    if ($entities && $form_state->getValue('also_delete_file')) {
      foreach ($entities as $entity) {
        if ($entity instanceof MediaInterface && $entity->access('delete', $this->currentUser)) {
          $file = $this->getFile($entity);
          if ($file) {
            if (!$file->access('delete', $this->currentUser)) {
              $file_inacessible_count++;
              continue;
            }

            $usages = $this->usageResolver->getFileUsages($file);
            if ($usages > 0) {
              $file_in_use_count++;
              continue;
            }

            $file->delete();
            $this->logger('media_file_delete')->notice('Deleted the associated file %name.', [
              '%name' => $file->getFilename(),
            ]);
            $file_deleted_count++;
          }
        }
      }

      if ($file_deleted_count) {
        $this->messenger->addStatus($this->formatPlural($file_deleted_count, 'Deleted @count associated file.', 'Deleted @count associated files.'));
      }
      if ($file_inacessible_count) {
        $this->messenger->addWarning($this->formatPlural($file_inacessible_count, 'Could not delete @count associated file because of insufficient privilege.', 'Could not delete @count associated files because of insufficient privilege.'));
      }
      if ($file_in_use_count) {
        $this->messenger->addWarning($this->formatPlural($file_in_use_count, 'Could not delete @count associated file because it is used in other places.', 'Could not delete @count associated files because they are used in other places.'));
      }
    }
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
    if (is_a($field->getItemDefinition()->getClass(), FileItem::class, TRUE)) {
      $fid = $media->getSource()->getSourceFieldValue($media);
      if ($fid) {
        return File::load($fid);
      }
    }
    return NULL;
  }

}
