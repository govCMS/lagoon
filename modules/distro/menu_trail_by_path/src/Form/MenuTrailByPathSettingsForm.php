<?php

namespace Drupal\menu_trail_by_path\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\menu_trail_by_path\MenuTrailByPathActiveTrail;

/**
 * Configures menu trail by path settings for this site.
 */
class MenuTrailByPathSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_trail_by_path_settings_form';
  }

  /**
   * Returns an array of trail source options.
   *
   * @return string[]
   *   An array of trail source options.
   */
  public static function getTrailSourceOptions() {
    return [
      MenuTrailByPathActiveTrail::MENU_TRAIL_PATH => t('By Path'),
      MenuTrailByPathActiveTrail::MENU_TRAIL_CORE => t('Drupal Core Behavior'),
      MenuTrailByPathActiveTrail::MENU_TRAIL_DISABLED => t('Disabled'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['menu_trail_by_path.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('menu_trail_by_path.settings');

    $form['max_path_parts'] = [
      '#type' => 'number',
      '#min' => 0,
      '#size' => 30,
      '#title' => $this->t('Maximum path parts'),
      '#default_value' => $config->get('max_path_parts'),
      '#description' => $this->t('Configures how deep the module should go when resolving active trail links. Setting this value to zero will not limit the number of the path parts. It is recommended to configure the path parts and enabled menu to only those that require it, to avoid unnecessary performance overhead. The path setting only applies when using the by path option.'),
    ];
    $form['trail_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Trail Source'),
      '#description' => $this->t('Configures the global behavior for the trail source. The trail source default can be overriden for each menu in the menu settings.'),
      '#options' => static::getTrailSourceOptions(),
      '#default_value' => $config->get('trail_source'),
    ];
    $form['trail_source'][MenuTrailByPathActiveTrail::MENU_TRAIL_PATH]['#description'] = t('Attempt to find a matching parent menu link based on the path structure. Slower, especially with a large amount of paths parts to consider.');
    $form['trail_source'][MenuTrailByPathActiveTrail::MENU_TRAIL_CORE]['#description'] = t('Active trail only for pages that have a menu link pointing to them, same as when not using this module.');
    $form['trail_source'][MenuTrailByPathActiveTrail::MENU_TRAIL_DISABLED]['#description'] = t('No active trail at all. No performance overhead, useful for special/footer menus.');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('menu_trail_by_path.settings');

    $config->set('max_path_parts', (int) $form_state->getValue('max_path_parts'));
    $config->set('trail_source', $form_state->getValue('trail_source'));
    $config->save();
  }

}
