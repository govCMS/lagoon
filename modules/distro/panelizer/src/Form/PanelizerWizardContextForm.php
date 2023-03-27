<?php

namespace Drupal\panelizer\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Form\ManageContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Simple wizard step form.
 */
class PanelizerWizardContextForm extends ManageContext {

  /**
   * {@inheritdoc}
   */
  protected $relationships = FALSE;

  /**
   * The shared temp store factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstoreFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->tempstoreFactory = $container->get('tempstore.shared');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panelizer_wizard_context_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getContextClass($cached_values) {
    return PanelizerWizardContextConfigure::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipClass($cached_values) {}

  /**
   * {@inheritdoc}
   */
  protected function getContextAddRoute($cached_values) {
    return 'panelizer.wizard.step.context.add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipAddRoute($cached_values) {
    return 'panelizer.wizard.step.context.add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getContexts($cached_values) {
    return $cached_values['plugin']->getPattern()->getDefaultContexts($this->tempstoreFactory, $this->getTempstoreId(), $this->machine_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTempstoreId() {
    return 'panelizer.wizard';
  }

  /**
   * {@inheritdoc}
   */
  protected function getContextOperationsRouteInfo($cached_values, $machine_name, $row) {
    return ['panelizer.wizard.step.context', [
      'machine_name' => $machine_name,
      'context_id' => $row,
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRelationshipOperationsRouteInfo($cached_values, $machine_name, $row) {
    return ['panelizer.wizard.step.context', [
      'machine_name' => $machine_name,
      'context_id' => $row,
    ]];
  }

  /**
   * {@inheritdoc}
   */
  protected function isEditableContext($cached_values, $row) {
    if (!isset($cached_values['contexts'][$row])) {
      return FALSE;
    }
    $context = $cached_values['contexts'][$row];
    return !empty($context['value']);
  }

  /**
   * {@inheritdoc}
   */
  public function addContext(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $context = $form_state->getValue('context');
    $content = $this->formBuilder->getForm($this->getContextClass($cached_values), $context, $this->getTempstoreId(), $this->machine_name);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    list(, $route_parameters) = $this->getContextOperationsRouteInfo($cached_values, $this->machine_name, $context);
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = Url::fromUri($this->getContextAddRoute($cached_values), ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]]);
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Add new context'), $content, ['width' => '700']));
    return $response;
  }

}
