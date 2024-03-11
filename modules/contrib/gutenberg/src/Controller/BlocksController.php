<?php

namespace Drupal\gutenberg\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\gutenberg\BlocksRendererHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Returns responses for our blocks routes.
 */
class BlocksController extends ControllerBase {

  /**
   * Drupal\Core\Block\BlockManagerInterface instance.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Render\Renderer instance.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Drupal\gutenberg\BlocksRendererHelper instance.
   *
   * @var \Drupal\gutenberg\BlocksRendererHelper
   */
  protected $blocksRenderer;

  /**
   * Drupal\Core\Plugin\Context\ContextRepositoryInterface
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * BlocksController constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Block manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Render service.
   * @param \Drupal\gutenberg\BlocksRendererHelper $blocks_renderer
   *   Blocks renderer helper service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   Context repository service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @return void
   */
  public function __construct(
    BlockManagerInterface $block_manager,
    ConfigFactoryInterface $config_factory,
    Renderer $renderer,
    BlocksRendererHelper $blocks_renderer,
    ContextRepositoryInterface $context_repository,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->blockManager = $block_manager;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->blocksRenderer = $blocks_renderer;
    $this->contextRepository = $context_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('gutenberg.blocks_renderer'),
      $container->get('context.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns JSON representing the loaded blocks.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $content_type
   *   The content type to fetch settings from.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function loadByType(Request $request, $content_type) {
    $config = $this->configFactory->getEditable('gutenberg.settings');
    $config_values = $config->get($content_type . '_allowed_drupal_blocks');

    // Get blocks definition.
    $definitions = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());
    $definitions = $this->blockManager->getSortedDefinitions($definitions);
    $groups = $this->blockManager->getGroupedDefinitions($definitions);
    foreach ($groups as $key => $blocks) {
      $group_reference = preg_replace('@[^a-z0-9-]+@', '_', strtolower($key));
      $groups['drupalblock/all_' . $group_reference] = $blocks;
      unset($groups[$key]);
    }

    $return = [];
    foreach ($config_values as $key => $value) {
      if ($value) {
        if (preg_match('/^drupalblock\/all/', $value)) {
          // Getting all blocks from group.
          foreach ($groups[$value] as $key_block => $definition) {
            $return[$key_block] = $definition;
          }
        }
        else {
          $return[$key] = $definitions[$key];
        }
      }
    }

    return new JsonResponse($return);
  }

  /**
   * Returns JSON representing the loaded blocks.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $plugin_id
   *   Plugin ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function loadById(Request $request, $plugin_id) {
    $request_content = $request->getContent();

    $config = [];
    if (!empty($request_content)) {
      $config = json_decode($request_content, TRUE);
    }

    $plugin_block = $this->blocksRenderer->getBlockFromPluginId($plugin_id, $config);

    $content = '';

    if ($plugin_block) {
      $access_result = $this->blocksRenderer->getBlockAccess($plugin_block);
      if ($access_result->isForbidden()) {
        // You might need to add some cache tags/contexts.
        return new JsonResponse([
          'access' => FALSE,
          'html' => $this->t('Unable to render block. Check block settings or permissions.'),
        ]);
      }

      $content = $this->blocksRenderer->getRenderFromBlockPlugin($plugin_block);
    }

    // If the block is a view with contexts defined, it may
    // not render on the editor because of, for example, the
    // node path. Let's just write some warning if no content.
    if ($content === '') {
      $content = $this->t('Unable to render the content possibly due to path restrictions.');
    }

    return new JsonResponse(['access' => TRUE, 'html' => $content]);
  }

  /**
   * Load content block types and returns a JSON response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $content_type
   *   The content type to fetch settings from.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function loadContentBlockTypes(Request $request, $content_type) {
    $config = $this->configFactory->getEditable('gutenberg.settings');
    $config_values = array_keys($config->get($content_type . '_allowed_content_block_types') ?? []);
    $content_block_types = $this->entityTypeManager->getStorage('block_content_type')->loadMultiple();
    $content_block_types = array_filter($content_block_types, function ($content_block_type) use ($config_values) {
      return
        $content_block_type->id() !== 'reusable_block'
        && in_array('content-block/' . $content_block_type->id(), $config_values);
    });

    $content_block_types = array_map(function ($content_block_type) {
      return [
        'id' => $content_block_type->id(),
        'label' => $content_block_type->label(),
        'description' => $content_block_type->getDescription(),
      ];
    }, $content_block_types);

    return new JsonResponse($content_block_types);
  }

  /**
   * Load content block by id and returns a JSON response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *  The request.
   * @param string $content_block_id
   *  The content block id.
   * @param string $view_mode
   *  The view mode.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *  The JSON response.
   */
  public function renderContentBlock(Request $request, $content_block_id, $view_mode = 'default') {
    $content_block = $this->entityTypeManager->getStorage('block_content')->load($content_block_id);

    if (!$content_block) {
      return new JsonResponse([
        'access' => FALSE,
        'html' => $this->t('Unable to render block. Check block settings or permissions.'),
      ]);
    }

    $render = $this
      ->entityTypeManager
      ->getViewBuilder('block_content')
      ->view($content_block, $view_mode);

    $build = [
      '#theme' => 'block',
      '#attributes' => [],
      '#contextual_links' => [],
      '#configuration' => [
        'provider' => 'gutenberg',
      ],
      '#plugin_id' => 'content_block:' . $content_block->bundle(),
      '#base_plugin_id' => 'content_block',
      '#derivative_plugin_id' => $content_block->bundle(),
      'content' => $render,
    ];

    $content = $this->renderer->renderRoot($build);

    return new JsonResponse(['access' => TRUE, 'html' => $content]);
  }
}
