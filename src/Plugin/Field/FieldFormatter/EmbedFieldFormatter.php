<?php

namespace Drupal\ezcontent_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'embed' formatter.
 *
 * @FieldFormatter(
 *   id = "ezcontent_embed",
 *   label = @Translation("EZContent Embed"),
 *   field_types = {
 *     "string",
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class EmbedFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];
    // Collect cache metadata.
    $cache_tags = [];
    $cache_contexts = [];
    $cache_max_age = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => $item->value,
      ];

      // Add cache metadata based on each item.
      // For example, if the content or language changes, this should be reflected.
      $cache_tags = array_merge($cache_tags, $item->getCacheTags());
      $cache_contexts = array_merge($cache_contexts, $item->getCacheContexts());
      $cache_max_age = Cache::mergeMaxAges($cache_max_age, $item->getCacheMaxAge());
    }

    // Attach cache metadata to the render array.
    $elements['#cache'] = [
      'tags' => $cache_tags,
      'contexts' => $cache_contexts,
      'max-age' => $cache_max_age,
    ];

    return $elements;
  }

}
