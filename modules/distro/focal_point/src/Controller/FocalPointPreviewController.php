<?php

namespace Drupal\focal_point\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget;
use Drupal\file\Entity\File;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Image\ImageFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class FocalPointPreviewController.
 *
 * @package Drupal\focal_point\Controller
 */
class FocalPointPreviewController extends ControllerBase {

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The request service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The file storage service.
   *
   * @var \Drupal\file\FileStorage
   */
  protected $fileStorage;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image_factory parameter.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request parameter.
   */
  public function __construct(ImageFactory $image_factory, RequestStack $request_stack) {
    $this->imageFactory = $image_factory;
    $this->request = $request_stack->getCurrentRequest();
    $this->fileStorage = $this->entityTypeManager()->getStorage('file');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('image.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\Validator\Exception\InvalidArgumentException
   */
  public function content($fid, $focal_point_value) {
    $output = [];
    $file = $this->fileStorage->load($fid);
    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      throw new InvalidArgumentException('The file with id = $fid is not an image.');
    }

    $styles = $this->getFocalPointImageStyles();

    // Since we are about to create a new preview of this image, we first must
    // flush the old one. This should not be a performance hit since there is
    // no good reason for anyone to preview an image unless they are changing
    // the focal point value.
    image_path_flush($image->getSource());

    $derivative_images = [];
    $derivative_image_note = '';

    $original_image = [
      '#theme' => 'image',
      '#uri' => $image->getSource(),
      '#alt' => $this->t('Focal Point Preview Image'),
      '#attributes' => [
        'id' => 'focal-point-preview-image',
      ],
    ];

    if (!empty($styles)) {
      foreach ($styles as $style) {
        $style_label = $style->get('label');
        $url = $this->buildUrl($style, $file, $focal_point_value);

        $derivative_images[$style->getName()] = [
          'style' => $style_label,
          'url' => $url,
          'image' => [
            '#theme' => 'image',
            '#uri' => $url,
            '#alt' => $this->t('Focal Point Preview: %label', ['%label' => $style_label]),
            '#attributes' => [
              'class' => ['focal-point-derivative-preview-image'],
            ],
          ],
        ];
      }
      $derivative_image_note = $this->t('Click an image to see a larger preview. You may need to scroll horizontally for more image styles.');
    }
    else {
      // There are no styles that use a focal point effect to preview.
      $image_styles_url = Url::fromRoute('entity.image_style.collection')->toString();
      $this->messenger()->addWarning(
        $this->t('You must have at least one <a href=":url">image style</a> defined that uses a focal point effect in order to preview.',
          [':url' => $image_styles_url]
        )
      );
    }

    $output['focal_point_preview_page'] = [
      '#theme' => "focal_point_preview_page",
      "#original_image" => $original_image,
      '#derivative_images' => $derivative_images,
      '#focal_point' => $focal_point_value,
      '#preview_image_note' => $this->t('This preview image above may have been scaled to fit on the page.'),
      '#derivative_image_note' => $derivative_image_note,
    ];

    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '80%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(
      new OpenModalDialogCommand($this->t('Images preview'), $output, $options)
    );

    return $response;
  }

  /**
   * Define access control for the preview page.
   *
   * Deny users access to the preview page unless they have permission to edit
   * an entity (any entity) that references the current image being previewed or
   * if they've provide a valid token as a query string. The later is needed so
   * preview will work when creating a new entity that has not yet been saved.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param int $fid
   *   The file id for the image being previewed from the URL.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An AccessResult object defining if permission is granted or not.
   */
  public function access(AccountInterface $account, $fid) {
    $access = AccessResult::forbidden();

    // @todo: I should be able to use "magic args" to load the file directly.
    $file = $this->fileStorage->load($fid);
    $image = $this->imageFactory->get($file->getFileUri());
    if (!$image->isValid()) {
      throw new InvalidArgumentException('The file with id = $fid is not an image.');
    }

    // Check if there was a valid token provided in with the HTTP request so
    // that preview is available on a "create entity" form.
    if ($this->validTokenProvided()) {
      $access = AccessResult::allowed();
    }

    // If access has not yet been granted and the file module is enabled, check
    // if there is an entity that references this file which the current user
    // has access to edit.
    if (function_exists('file_get_file_references') && !$access->isAllowed()) {
      $references = file_get_file_references($file, NULL, EntityStorageInterface::FIELD_LOAD_REVISION, '');
      foreach ($references as $data) {
        foreach (array_keys($data) as $entity_type_id) {
          if ($account->hasPermission($entity_type_id . ".edit")) {
            $access = AccessResult::allowed();
            break;
          }
        }
      }

    }

    return $access;
  }

  /**
   * Build a list of image styles that include an effect defined by focal point.
   *
   * @return array
   *   An array of machine names of image styles that use a focal point effect.
   */
  public function getFocalPointImageStyles() {
    // @todo: Can this be generated? See $imageEffectManager->getDefinitions();
    $focal_point_effects = ['focal_point_crop', 'focal_point_scale_and_crop'];

    $styles_using_focal_point = [];
    $styles = $this->entityTypeManager()->getStorage('image_style')->loadMultiple();
    foreach ($styles as $image_style_id => $style) {
      foreach ($style->getEffects() as $effect) {
        $style_using_focal_point = in_array($effect->getPluginId(), $focal_point_effects, TRUE);
        if ($style_using_focal_point) {
          $styles_using_focal_point[$image_style_id] = $style;
          break;
        }
      }
    }

    return $styles_using_focal_point;
  }

  /**
   * Create the URL for a preview image including a query parameter.
   *
   * @param \Drupal\image\ImageStyleInterface $style
   *   The image style being previewed.
   * @param \Drupal\file\Entity\File $image
   *   The image being previewed.
   * @param string $focal_point_value
   *   The focal point being previewed in the format XxY where x and y are the
   *   left and top offsets in percentages.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   The URL of the preview image.
   */
  protected function buildUrl(ImageStyleInterface $style, File $image, $focal_point_value) {
    $url = $style->buildUrl($image->getFileUri());
    $url .= (strpos($url, '?') !== FALSE ? '&' : '?') . 'focal_point_preview_value=' . $focal_point_value;

    return $url;
  }

  /**
   * Was a valid token found?
   *
   * Determine if a valid focal point token was provided in the query string of
   * the current request. If no token is provided in the query string then this
   * method will return FALSE.
   *
   * @return bool
   *   Indicates if a valid token was provided in the query string.
   */
  protected function validTokenProvided() {
    try {
      if (\Drupal::request()->query->has('focal_point_token')) {
        $token = \Drupal::request()->query->get('focal_point_token');
        return FocalPointImageWidget::validatePreviewToken($token);
      }
      else {
        return FALSE;
      }
    }
    catch (\InvalidArgumentException $e) {
      return FALSE;
    }
  }

}
