<?php

namespace Drupal\context\Reaction\Blocks\Controller;

use Drupal\context\ContextManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Url;
use Drupal\context\ContextInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller for the Block Context Reaction.
 */
class ContextReactionBlocksController extends ControllerBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
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
   * The Context modules context manager.
   *
   * @var \Drupal\context\ContextManager
   */
  protected $contextManager;

  /**
   * Constructs a new ContextReactionBlocksController object.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   *   The block manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The Drupal context repository.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The handler of the available themes.
   * @param \Drupal\context\ContextManager $contextManager
   *   The Context modules context manager.
   */
  public function __construct(
    BlockManagerInterface $blockManager,
    ContextRepositoryInterface $contextRepository,
    ThemeHandlerInterface $themeHandler,
    ContextManager $contextManager
  ) {
    $this->blockManager = $blockManager;
    $this->contextRepository = $contextRepository;
    $this->themeHandler = $themeHandler;
    $this->contextManager = $contextManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('theme_handler'),
      $container->get('context.manager')
    );
  }

  /**
   * Display a library of blocks that can be added to the context reaction.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\context\ContextInterface $context
   *   The context the blocks reaction belongs to.
   * @param string $reaction_id
   *   The ID of the blocks reaction that the selected block
   *   should be added to.
   *
   * @return array
   *   Array to build the add block page.
   */
  public function blocksLibrary(Request $request, ContextInterface $context, $reaction_id) {

    // If a theme has been defined in the query string then use this for
    // the add block link, default back to the default theme.
    $theme = $request->query->get('theme', $this->themeHandler->getDefault());

    // Only add blocks which work without any available context.
    $blocks = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());

    // Order by category, and then by admin label.
    $blocks = $this->blockManager->getSortedDefinitions($blocks);

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by block name'),
      '#attributes' => [
        'class' => ['context-table-filter'],
        'data-element' => '.block-add-table',
        'title' => $this->t('Enter a part of the block name to filter by.'),
      ],
    ];

    $headers = [
      $this->t('Block'),
      $this->t('Category'),
      $this->t('Operations'),
    ];

    $build['blocks'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => [],
      '#empty' => $this->t('No blocks available.'),
      '#attributes' => [
        'class' => ['block-add-table'],
      ],
    ];

    // Add each block definition to the table.
    foreach ($blocks as $block_id => $block) {
      $links = [
        'add' => [
          'title' => $this->t('Place block'),
          'url' => Url::fromRoute('context.reaction.blocks.block_add', [
            'context' => $context->id(),
            'reaction_id' => $reaction_id,
            'block_id' => $block_id,
          ], [
            'query' => [
              'theme' => $theme,
            ],
          ]),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 700,
            ]),
          ],
        ],
      ];

      $build['blocks']['#rows'][] = [
        'title' => [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '<div class="context-table-filter-text-source">{{ label }}</div>',
            '#context' => [
              'label' => $block['admin_label'],
            ],
          ],
        ],
        'category' => [
          'data' => $block['category'],
        ],
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ],
      ];
    }

    $build['#attached']['library'][] = 'context_ui/admin';

    return $build;
  }

  /**
   * Callback for the theme select list on the Context blocks reaction form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\context\ContextInterface $context
   *   The context the block reaction is located on.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function blocksFormThemeSelect(Request $request, ContextInterface $context) {
    $theme = $request->request->get('reactions[blocks][theme]', '', TRUE);

    // Get the context form and supply it with the blocks theme value.
    $form = $this->contextManager->getForm($context, 'edit', [
      'reactions' => [
        'blocks' => [
          'theme' => $theme,
        ],
      ],
    ]);

    $response = new AjaxResponse();

    $response->addCommand(new ReplaceCommand('#context-reactions', $form['reactions']));

    return $response;
  }

}
