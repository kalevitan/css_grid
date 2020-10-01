<?php

namespace Drupal\css_grid;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Render\Element;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Class LayoutBuilderAlter.
 *
 * Performs alterations to the Layout Builder render element.
 *
 * @package Drupal\css_grid
 */
class LayoutBuilderAlter {

  use LayoutBuilderContextTrait;

  /**
   * Alters the Layout builder element.
   *
   * @param array $element
   *   The Layout Builder element render array.
   *
   * @return array
   *   The modified Layout Builder element render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function preRender(array $element) {

  }
}