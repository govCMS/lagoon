<?php

namespace Drupal\context\Reaction\Blocks\Form;

use Drupal\block\BlockRepositoryInterface;
use Drupal\block\Entity\Block;
use Drupal\context\ContextManager;
use Drupal\context\ContextReactionManager;
use Drupal\context\Form\AjaxFormTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\context\ContextInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Block Form Base for blocks reactions.
 */
abstract class BlockFormBase extends FormBase {

  use AjaxFormTrait;

  /**
   * The plugin being configured.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $block;

  /**
   * The context entity the reaction belongs to.
   *
   * @var \Drupal\context\ContextInterface
   */
  protected $context;

  /**
   * The blocks reaction this block should be added to.
   *
   * @var \Drupal\context\Plugin\ContextReaction\Blocks
   */
  protected $reaction;

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * The Drupal context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The handler of the available themes.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The context reaction manager.
   *
   * @var \Drupal\context\ContextReactionManager
   */
  protected $contextReactionManager;

  /**
   * The Context modules context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new VariantPluginFormBase.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The Drupal context repository.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The handler of the available themes.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\context\ContextReactionManager $contextReactionManager
   *   The context reaction manager.
   * @param \Drupal\context\ContextManager $contextManager
   *   The Context modules context manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current request.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    PluginManagerInterface $block_manager,
    ContextRepositoryInterface $contextRepository,
    ThemeHandlerInterface $themeHandler,
    FormBuilderInterface $formBuilder,
    ContextReactionManager $contextReactionManager,
    ContextManager $contextManager,
    RequestStack $requestStack,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->blockManager = $block_manager;
    $this->contextRepository = $contextRepository;
    $this->themeHandler = $themeHandler;
    $this->formBuilder = $formBuilder;
    $this->contextReactionManager = $contextReactionManager;
    $this->contextManager = $contextManager;
    $this->request = $requestStack->getCurrentRequest();
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('theme_handler'),
      $container->get('form_builder'),
      $container->get('plugin.manager.context_reaction'),
      $container->get('context.manager'),
      $container->get('request_stack'),
      $container->get('module_handler')
    );
  }

  /**
   * Prepares the block plugin based on the block ID.
   *
   * @param string $block_id
   *   Either a block ID, or the plugin ID used to create a new block.
   *
   * @return \Drupal\Core\Block\BlockPluginInterface
   *   The block plugin.
   */
  abstract protected function prepareBlock($block_id);

  /**
   * Get the value to use for the submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   An object that, when cast to a string, returns the translated string.
   */
  abstract protected function getSubmitValue();

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\context\ContextInterface $context
   *   The context the reaction belongs to.
   * @param string|null $reaction_id
   *   The ID of the blocks reaction the block should be added to.
   * @param string|null $block_id
   *   The ID of the block to show a configuration form for.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContextInterface $context = NULL, $reaction_id = NULL, $block_id = NULL) {
    $this->context = $context;

    $this->reaction = $this->context->getReaction($reaction_id);
    $this->block = $this->prepareBlock($block_id);

    // If a theme was defined in the query use this theme for the block
    // otherwise use the default theme.
    $theme = $this->getRequest()->query->get('theme', $this->themeHandler->getDefault());

    // Some blocks require the theme name in the form state like Site Branding.
    $form_state->set('block_theme', $theme);

    // Some blocks require contexts, set a temporary value with gathered
    // contextual values.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $configuration = $this->block->getConfiguration();

    $form['#tree'] = TRUE;

    $form['settings'] = $this->block->buildConfigurationForm([], $form_state);

    $form['settings']['id'] = [
      '#type' => 'value',
      '#value' => $this->block->getPluginId(),
    ];

    $form['custom_id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this block instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => isset($configuration['custom_id']) ? $configuration['custom_id'] : preg_replace("/\W+/", "_", $this->block->getPluginId()),
      '#machine_name' => [
        'source' => ['settings', 'label'],
      ],
      '#required' => TRUE,
    ];

    $form['region'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t('Select the region where this block should be displayed.'),
      '#options' => $this->getThemeRegionOptions($theme),
      '#default_value' => isset($configuration['region']) ? $configuration['region'] : '',
    ];

    $form['unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique'),
      '#description' => $this->t('Check if the block should be uniquely placed. This means that the block can not be overridden by other blocks of the same type in the selected region. Most often you want this checked if a block unintentionally contains the same content as another block on the same page.'),
      '#default_value' => isset($configuration['unique']) ? $configuration['unique'] : FALSE,
    ];

    $form['theme'] = [
      '#type' => 'value',
      '#value' => $theme,
    ];

    $form['css_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block Class'),
      '#default_value' => isset($configuration['css_class']) ? $configuration['css_class'] : '',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->getSubmitValue(),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::submitFormAjax',
      ],
    ];

    // Remove ajax from submit, if this is not ajax request.
    if (!$this->request->isXmlHttpRequest()) {
      unset($form['actions']['submit']['#ajax']);
    }

    // Disable cache on form to prevent ajax forms from failing.
    $form_state->disableCache();

    // Call hook_form_alter and hook_form_block_form_alter so form alter hooks
    // changing the block_form will also be called here for e.g. adding
    // third party settings.
    $dummy_form_id = 'block_form';
    $this->moduleHandler->alter(['form', 'form_block_form'], $form, $form_state, $dummy_form_id);

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $settings = (new FormState())->setValues($form_state->getValue('settings'));

    // Call the plugin validate handler.
    $this->block->validateConfigurationForm($form['settings'], $settings);

    // Update the original form values, including errors.
    $form_state->setValue('settings', $settings->getValues());
    foreach ($settings->getErrors() as $name => $error) {
      $form_state->setErrorByName($name, $error);
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = SubformState::createForSubform($form['settings'], $form, $form_state);

    // Call the plugin submit handler.
    $this->block->submitConfigurationForm($form, $settings);

    // Update the original form values.
    $form_state->setValue('settings', $settings->getValues());

    // Add available contexts if this is a context aware block.
    if ($this->block instanceof ContextAwarePluginInterface) {
      $this->block->setContextMapping($form_state->getValue(['settings', 'context_mapping'], []));
    }

    $configuration = array_merge($this->block->getConfiguration(), [
      'custom_id' => $form_state->getValue('custom_id'),
      'region' => $form_state->getValue('region'),
      'theme' => $form_state->getValue('theme'),
      'css_class' => $form_state->getValue('css_class'),
      'unique' => $form_state->getValue('unique'),
      'context_id' => $this->context->id(),
      'third_party_settings' => $form_state->getValue('third_party_settings', []),
    ]);

    // Add/Update the block.
    if (!isset($configuration['uuid'])) {
      $this->reaction->addBlock($configuration);
    }
    else {
      $this->reaction->updateBlock($configuration['uuid'], $configuration);
    }

    $this->context->save();

    $form_state->setRedirectUrl(Url::fromRoute('entity.context.edit_form', [
      'context' => $this->context->id(),
    ]));
  }

  /**
   * Handle when the form is submitted trough AJAX.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response.
   */
  public function submitFormAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      $messages = StatusMessages::renderMessages(NULL);
      $output[] = $messages;
      $output[] = $form;
      $form_class = '.' . str_replace('_', '-', $form_state->getFormObject()->getFormId());
      // Remove any previously added error messages.
      $response->addCommand(new RemoveCommand('#drupal-modal .messages--error'));
      // Replace old form with new one and with error message.
      $response->addCommand(new ReplaceCommand($form_class, $output));
    }
    else {
      $form = $this->contextManager->getForm($this->context, 'edit');
      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new ReplaceCommand('#context-reactions', $form['reactions']));
    }

    return $response;
  }

  /**
   * Get a list of regions for the select list.
   *
   * @param string $theme
   *   The theme to get a list of regions for.
   * @param string $show
   *   What type of regions that should be returned, defaults to all regions.
   *
   * @return array
   *   The regions of the theme.
   */
  protected function getThemeRegionOptions($theme, $show = BlockRepositoryInterface::REGIONS_ALL) {
    $regions = system_region_list($theme, $show);

    foreach ($regions as $region => $title) {
      $regions[$region] = $title;
    }

    return $regions;
  }

  /**
   * Returns a block entity based on the configured values in context.
   *
   * Method that can be used by modules depending on
   * hook_form_block_form_alter(). Since that form is an entity form, the
   * getEntity method is available. Since hook_form_block_form_alter is also
   * called in this form, this will break modules depending on this method.
   *
   * @return \Drupal\block\BlockInterface
   *   A block entity.
   */
  public function getEntity() {
    return Block::create($this->block->getConfiguration() + ['plugin' => $this->block->getPluginId()]);
  }

}
