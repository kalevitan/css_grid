<?php

namespace Drupal\css_grid\Plugin\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Layout builder class for a CSS Grid Layout.
 */
class CssGrid extends LayoutDefault implements PluginFormInterface {

  /**
   * Span unit suffix options.
   */
  public const UNIT_OPTIONS = [
    '%' => '%',
    'auto' => 'auto',
    'em' => 'em',
    'fr' => 'fr',
    'max-content' => 'max-content',
    'min-content' => 'min-content',
    'minmax' => 'minmax',
    'px' => 'px',
  ];

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'grid_cells' => '',
      'grid_columns' => [], 
      'grid_rows' => [],
      'grid_gap' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div class="layout-settings-section mt-2">';
    $form['#suffix'] = '</div>';

    $form['grid_columns'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Grid Columns'),
      '#prefix' => '<div id="grid-columns-wrapper" class="mt-1">',
      '#suffix' => '</div>',
      '#weight' => 1,
    ];

    if (!$form_state->has('grid_columns')) {
      $form_state->set('grid_columns', $this->configuration['grid_columns']);
    }

    // Build a table for plugin settings.
    $form['grid_columns']['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Span'),
        $this->t('Unit'),
      ],
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => ['mt-1'],
          ],
        ],
      ],
    ];

    // Retrieve items from form state.
    $column_items = $form_state->get('grid_columns');
    $num_column_items = count($column_items);

    // Create form inputs for each item.
    for ($i = 0; $i < $num_column_items; $i++) {
      $form['grid_columns']['items'][$i] = [
        'value' => [
          '#type' => 'number',
          '#title' => $this->t('Span @index', ['@index' => $i + 1]),
          '#title_display' => 'invisible',
          '#default_value' => $column_items[$i]['value'] ?? 1,
          '#max' => 2000,
          '#min' => .5,
          '#step' => .5,
        ],
        'unit' => [
          '#type' => 'select',
          '#title' => $this->t('Unit @index', ['@index' => $i + 1]),
          '#title_display' => 'invisible',
          '#default_value' => $column_items[$i]['unit'] ?? 'fr',
          '#options' => static::UNIT_OPTIONS,
        ],
      ];
    }

    $form['grid_columns']['actions'] = [
      '#type' => 'actions',
    ];

    // Add an item button.
    $form['grid_columns']['actions']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add column'),
      '#submit' => [[$this, 'addRow']],
      '#ajax' => [
        'callback' => [$this, 'layoutColumnsSettingsCallback'],
        'wrapper' => 'grid-columns-wrapper',
      ],
      '#name' => 'grid_columns',
      '#weight' => 2,
      '#prefix' => '<div class="grid-columns-buttons mt-3">',
      '#suffix' => '</div>',
    ];

    if ($num_column_items > 1) {
      $form['grid_columns']['actions']['remove_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove item'),
        '#submit' => [[$this, 'removeLastRow']],
        '#ajax' => [
          'callback' => [$this, 'layoutColumnsSettingsCallback'],
          'wrapper' => 'grid-columns-wrapper',
        ],
        '#name' => 'grid_columns',
        '#weight' => 1,
        '#prefix' => '<div class="mt-3 mb-n3">',
        '#suffix' => '</div>',
      ];
    }

    $form['grid_rows'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Grid Rows'),
      '#prefix' => '<div id="grid-rows-wrapper" class="mt-1">',
      '#suffix' => '</div>',
      '#weight' => 1,
    ];

    if (!$form_state->has('grid_rows')) {
      $form_state->set('grid_rows', $this->configuration['grid_rows']);
    }

    // Build a table for plugin settings.
    $form['grid_rows']['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Span'),
        $this->t('Unit'),
      ],
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => ['mt-1'],
          ],
        ],
      ],
    ];

    // Retrieve items from form state.
    $row_items = $form_state->get('grid_rows');
    $num_row_items = count($row_items);

    // Create form inputs for each item.
    for ($i = 0; $i < $num_row_items; $i++) {
      $form['grid_rows']['items'][$i] = [
        'value' => [
          '#type' => 'number',
          '#title' => $this->t('Span @index', ['@index' => $i + 1]),
          '#title_display' => 'invisible',
          '#default_value' => $row_items[$i]['value'] ?? 1,
          '#max' => 2000,
          '#min' => .5,
          '#step' => .5,
        ],
        'unit' => [
          '#type' => 'select',
          '#title' => $this->t('Unit @index', ['@index' => $i + 1]),
          '#title_display' => 'invisible',
          '#default_value' => $row_items[$i]['unit'] ?? 'fr',
          '#options' => static::UNIT_OPTIONS,
        ],
      ];
    }

    $form['grid_rows']['actions'] = [
      '#type' => 'actions',
    ];

    // Add an item button.
    $form['grid_rows']['actions']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add row'),
      '#submit' => [[$this, 'addRow']],
      '#ajax' => [
        'callback' => [$this, 'layoutRowsSettingsCallback'],
        'wrapper' => 'grid-rows-wrapper',
      ],
      '#name' => 'grid_rows',
      '#weight' => 2,
      '#prefix' => '<div class="grid-rows-buttons mt-3">',
      '#suffix' => '</div>',
    ];

    if ($num_row_items > 1) {
        $form['grid_rows']['actions']['remove_item'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove item'),
        '#submit' => [[$this, 'removeLastRow']],
        '#ajax' => [
          'callback' => [$this, 'layoutRowsSettingsCallback'],
          'wrapper' => 'grid-rows-wrapper',
        ],
        '#name' => 'grid_rows',
        '#weight' => 1,
        '#prefix' => '<div class="mt-3 mb-n3">',
        '#suffix' => '</div>',
      ];

      $form['grid_rows']['actions']['area_join'] = [
        '#type' => 'submit',
        '#value' => $this->t('Join Template Areas'),
        '#submit' => [[$this, 'joinTemplateAreas']],
        '#ajax' => [
          'callback' => [$this, 'layoutRowsSettingsCallback'],
          'wrapper' => 'grid-rows-wrapper',
        ],
        '#name' => 'grid_rows',
        '#weight' => 10,
        '#prefix' => '<div class="mt-2">',
        '#suffix' => '</div>',
      ];
    }

    $form['grid_gap'] = [
      '#type' => 'number',
      '#title' => $this->t('Grid gap (rems)'),
      '#default_value' => $this->configuration['grid_gap'] ?? 1,
      '#max' => 20,
      '#min' => .5,
      '#step' => .5,
      '#weight' => 999,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    // Initialize our grid_cell config.
    $this->configuration['grid_cells'] = '';

    $config_types = ['grid_columns', 'grid_rows'];
    foreach ($values as $key => $value) {
      if (in_array($key, $config_types)) {
        if (isset($value['items'])) {
          $items = $value['items'];
          // Collect grid region section settings.
          $section_settings = [];
          for ($i = 0; $i < count($items); $i++) {
            $section_settings[$i]['value'] = $items[$i]['value'];
            $section_settings[$i]['unit'] = $items[$i]['unit'];
            $section_settings[$i]['type'] = $key;
          }
          // Store the processed settings as plugin config.
          $this->configuration[$key] = $section_settings;
          // Calculate and store the number of cells to render.
          if (is_numeric($this->configuration['grid_cells'])) {
            $this->configuration['grid_cells'] *= count($items);
          }
          else {
            $this->configuration['grid_cells'] = count($items);
          }
        }
      }
    }
    if ($values['grid_gap']) {
      $this->configuration['grid_gap'] = $values['grid_gap'];
    }
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $regions) {
    $this->setPluginDefinitionRegions();
    $build = parent::build($regions);

    // @todo How do we move away from inline style assignments?
    $build['#attributes'] = [
      'class' => ['grid-layout'],
      'style' => [
        'grid-template-columns: ' . $this->computeGridProperties('grid_columns') . ';',
        'grid-template-rows: ' . $this->computeGridProperties('grid_rows') . ';',
        'gap: ' . $this->configuration['grid_gap'] . 'rem;',
      ],
    ];

    // Attach our base CSS Grid styling.
    $build['#attached']['library'][] = 'css_grid/css_grid';

    return $build;
  }

  /**
   * Set our dynamic block regions.
   */
  protected function setPluginDefinitionRegions() {
    $regionMap = [];

    $grid_cells = $this->configuration['grid_cells'];
    $grid_columns = $this->configuration['grid_columns'];
    $grid_rows = $this->configuration['grid_rows'];

    foreach (range(1, $grid_cells) as $i) {
      $regionMap['content_' . $i] = [
        'label' => $this->t('Content @i', ['@i' => $i]),
      ];
    }
    $this->pluginDefinition->setRegions($regionMap);
  }

  /**
   * Compute the grid properties.
   * 
   * @param string $property
   *   The grid config property.
   */
  protected function computeGridProperties($property) {
    $grid_property = $this->configuration[$property];
    array_walk($grid_property, function (&$value) {
      $value = $value['value'] . $value['unit'];
    });

    return implode(' ', $grid_property);
  }

  /**
   * Remove last row.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface.
   */
  public function removeLastRow(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $type = $trigger_element['#name'];

    $items = [];
    if ($form_state->has($type)) {
      $items = $form_state->get($type);
      array_pop($items);
    }
    $form_state->set($type, $items);
    $form_state->setRebuild();
  }

  /**
   * Add new row.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface.
   */
  public function addRow(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $type = $trigger_element['#name'];

    $items = [];
    if ($form_state->has($type)) {
      $items = $form_state->get($type);
      $nextItem = count($items);
      $items[$nextItem]['type'] = $type;
    }
    else {
      $nextItem = count($items);
      $items[$nextItem];
    }
    
    $form_state->set($type, $items);
    $form_state->setRebuild();
  }

  /**
   * Layout settings callback.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface.
   *
   * @return array
   *   Return the layout settings form.
   */
  public function layoutColumnsSettingsCallback(array &$form, FormStateInterface $form_state) {
    return $form['layout_settings']['grid_columns'];
  }

  /**
   * Layout settings callback.
   *
   * @param array $form
   *   The array form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormStateInterface.
   *
   * @return array
   *   Return the layout settings form.
   */
  public function layoutRowsSettingsCallback(array &$form, FormStateInterface $form_state) {
    return $form['layout_settings']['grid_rows'];
  }

}