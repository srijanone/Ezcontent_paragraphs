<?php

namespace Drupal\ezcontent_paragraphs\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\field_group\Element\HtmlElement as HtmlElementFormatter;
use Drupal\field_group\Plugin\field_group\FieldGroupFormatter\HtmlElement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin implementation of the 'html_element_hero_media_display' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "html_element_hero_media_display",
 *   label = @Translation("HTML element hero media display"),
 *   description = @Translation("This fieldgroup renders the inner content in a
 *   HTML element with classes and attributes."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class HtmlElementHeroMediaDisplay extends HtmlElement implements ContainerFactoryPluginInterface {

  /**
   * The hex2rgba service.
   *
   * @var \Drupal\ezcontent_paragraphs\Hex2Rgba
   */
  protected $hex2rgbaService;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a HtmlElementHeroMediaDisplay object.
   *
   * @param \Drupal\ezcontent_paragraphs\Hex2Rgba $hex2rgba
   *   The hex2rgba service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct($hex2rgba, LoggerInterface $logger) {
    $this->hex2rgbaService = $hex2rgba;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('ezcontent_paragraphs.hex2rgba'),
      $container->get('logger.channel.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);
    $paragraph = $rendering_object['#paragraph'];
    $element_attributes = new Attribute();

    // Set attributes such as id, classes, and style using helper methods.
    $this->setElementAttributes($element_attributes, $paragraph);
    $this->applyBackgroundColor($element_attributes, $paragraph);

    $element['#attributes'] = $element_attributes;
    $element['#type'] = 'field_group_html_element';
    $element['#wrapper_element'] = $this->getSetting('element');
    $element['#effect'] = $this->getSetting('effect');
    $element['#speed'] = $this->getSetting('speed');

    if ($this->getSetting('show_label')) {
      $element['#title_element'] = $this->getSetting('label_element');
      $element['#title'] = Html::escape($this->getLabel());
    }

    // Process the field group HTML element.
    $form_state = new FormState();
    HtmlElementFormatter::processHtmlElement($element, $form_state);

    // Attach additional libraries if required.
    if ($this->getSetting('required_fields')) {
      $element['#attributes']['class'][] = 'field-group-html-element';
      $element['#attached']['library'][] = 'field_group/formatter.html_element';
      $element['#attached']['library'][] = 'field_group/core';
    }
  }

  /**
   * Helper method to set element attributes such as ID and classes.
   *
   * @param \Drupal\Core\Template\Attribute $element_attributes
   *   The attributes array to modify.
   * @param object $paragraph
   *   The paragraph entity.
   */
  protected function setElementAttributes(Attribute $element_attributes, $paragraph) {
    // Set attributes based on settings.
    if ($this->getSetting('attributes')) {
      try {
        preg_match_all('/([^\s=]+)="([^"]+)"/', $this->getSetting('attributes'), $matches);
        foreach ($matches[1] as $key => $attribute) {
          $element_attributes[$attribute] = $matches[2][$key];
        }
      }
      catch (\Exception $e) {
        $this->logger->error('Invalid attribute string: @error', ['@error' => $e->getMessage()]);
      }
    }

    // Set ID attribute if present.
    if ($this->getSetting('id')) {
      $element_attributes['id'] = Html::getId($this->getSetting('id'));
    }

    // Set classes and add text position.
    $classes = $this->getClasses();
    if (!empty($paragraph->field_text_position)) {
      $classes[] = $paragraph->field_text_position->value;
    }

    // Use addClass() to safely handle class additions.
    $element_attributes->addClass($classes);
  }

  /**
   * Helper method to apply background color styling.
   *
   * @param \Drupal\Core\Template\Attribute $element_attributes
   *   The attributes array to modify.
   * @param object $paragraph
   *   The paragraph entity.
   */
  protected function applyBackgroundColor(Attribute $element_attributes, $paragraph) {
    // Ensure field_text_background_color exists on the entity.
    if ($paragraph->hasField('field_text_background_color') && !$paragraph->get('field_text_background_color')->isEmpty()) {
      $bg = $paragraph->get('field_text_background_color')->first()->getValue();
    }
    else {
      // Handle cases where field config is missing or null.
      $bg_color_field_config = \Drupal::entityTypeManager()
        ->getStorage('field_config')
        ->load('paragraph.card.field_text_background_color');
      if ($bg_color_field_config instanceof FieldConfigInterface) {
        $default_value = $bg_color_field_config->get('default_value');
        $bg = $default_value[0];
      }
      else {
        $this->logger->warning('Background color field configuration is missing.');
        $bg = ['color' => '#ffffff', 'opacity' => '1'];  // Fallback to default.
      }
    }

    // Convert hex to RGBA.
    $bg_color = $this->hex2rgbaService->hex2rgba($bg['color'], $bg['opacity']);
    $element_attributes->setAttribute('style', "background-color: $bg_color;");
  }

}
