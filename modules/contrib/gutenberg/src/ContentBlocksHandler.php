<?php

namespace Drupal\gutenberg;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\gutenberg\Controller\UtilsController;
use Drupal\gutenberg\Parser\BlockParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the mappingFields configuration manipulation.
 *
 * This class contains functions primarily for processing mappingFields
 * configurations.
 */
class ContentBlocksHandler implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Gutenberg logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * EntityTypePresave constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Gutenberg logger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->logger = $logger;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.channel.gutenberg')
    );
  }

  /**
   * Process content blocks. Get content blocks from previous content and compare with new content.
   * Blocks no longer on new content will be deleted.
   * 
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function processBlocks(EntityInterface $entity) {
    $text_fields = UtilsController::getEntityTextFields($entity);

    if (count($text_fields) === 0) {
      return;
    }

    $field_content = $entity->get($text_fields[0])->getString();
    $original_field_content = '';
    if (!$entity->isNew()) {
      $original_field_content = $entity->original->get($text_fields[0])->getString();
    }

    // Fetch only blocks with mapping fields.
    $block_parser = new BlockParser();
    $blocks = $this->getContentBlocks($block_parser->parse($field_content));
    $original_blocks = $this->getContentBlocks($block_parser->parse($original_field_content));

    $blocks_ids = array_map(function ($block) {
      return $block['attrs']['contentBlockId'];
    }, $blocks);

    $original_blocks_ids = array_map(function ($block) {
      return $block['attrs']['contentBlockId'];
    }, $original_blocks);

    $ids_to_remove = array_diff($original_blocks_ids, $blocks_ids);
    // $ids_added = array_diff($blocks_ids, $original_blocks_ids);

    $blocks_to_remove = BlockContent::loadMultiple($ids_to_remove);

    foreach ($blocks_to_remove as $block) {
      $block->delete();
    }
  }

  /**
   * Get all content blocks, inner blocks included from content.
   */
  public function getContentBlocks($blocks) {
    $content_blocks = [];
    foreach ($blocks as $block) {
      if (count($block['innerBlocks']) > 0) {
        $content_blocks = array_merge($content_blocks, $this->getContentBlocks($block['innerBlocks']));
      }
      else {
        if ($block['blockName'] && str_contains($block['blockName'], 'content-block/')) {
          $content_blocks[] = $block;
        }
      }
    }
    return $content_blocks;
  }
}
