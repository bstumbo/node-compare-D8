<?php

/**
 * @file
 * Theme functions for the Node Compare.
 */

function node_compare_preprocess_block(&$variables) {
    if ($variables['block']->delta == 'node_compare_items') {
      $variables['block_html_id'] = 'node-compare-block-content';
    }
  }

/**
 * Preprocess for the comparison page.
 */
function template_preprocess_node_compare_template(&$variables) {
  $variables['only_diff_checkbox'] = array(
    '#type' => 'checkbox',
    '#title' => \Drupal::config('node_compare.settings')->get('node_compare_block.node_compare_only_diff'),
    '#attributes' => array(
      'id' => array('compare-only-diff'),
    ),
    '#prefix' => '<div id="compare-view-mode-box" style="display: none;">',
    '#suffix' => '</div>',
  );

  $variables['comparison_table']['#prefix'] = '<div id="compare-content-wrapper">';
  $variables['comparison_table']['#suffix'] = '</div>';
  $variables['comparison_table']['#attributes'] = array(
    'id' => 'comparison-table',
  );
  /*$variables['comparison_table']['#attached'] = array(
    'js' => array(
      drupal_get_path('module', 'node_compare') . '/theme/compare-table.js',
    ),
  );*/
}

/**
 * Theming a block content.
 */
function theme_node_compare_block_content($vars) {
  $output = '';
  if (isset($_SESSION['node_compare']['nids'])) {
    $sess = $_SESSION['node_compare'];
    $rows = array();
    foreach ($sess['nids'] as $nid => $title) {
      $rows[] = array($title, theme('node_compare_toggle_link', array('nid' => $nid, 'block' => TRUE)));
    }
    if (count($sess['nids']) > 1) {
      $options = array(
        'attributes' => array(
          'class' => array('compare-block-links'),
        ),
      );
      $links = array();
      $links[] = l(t('Compare Selected'), 'compare/me', $options);
      $options['query'] = drupal_get_destination();
      $options['attributes']['class'][] = 'use-ajax';
      $links[] = l(t('Clear'), 'compare/clear/nojs', $options);
      $rows[] = $links;
    }
    $output = theme('table', array('header' => NULL, 'rows' => $rows));
  }
  if (isset($_SESSION['node_compare_history'])) {
    $items = array();
    foreach ($_SESSION['node_compare_history'] as $date => $link) {
      $items[] = l(format_date($date), $link);
    }
    $output .= theme('item_list', array('items' => $items, 'title' => t('Your recent comparisons:')));
  }
  return $output;
}

/**
 * Theming a link to add/remove nodes for compares.
 */
function theme_node_compare_toggle_link($vars) {
  $id = 'compare-toggle-' . $vars['nid'];
  $node_added = isset($_SESSION['node_compare']['nids'][$vars['nid']]);
  $action_class = '';
  $remove_t = variable_get('node_compare_text_remove', 'Remove from comparison');

  if ($vars['block']) {
    $id .= '-block';
    $path = $GLOBALS['base_path'] . 'misc/message-16-error.png';
    $text = '<img title="' . $remove_t . '" src="' . $path . '">';
  }
  else {
    $text = $node_added ? $remove_t : \Drupal::config('node_compare.settings')->get('node_compare_text_add');
    $action_class = $node_added ? 'remove' : 'add';
  }
  $options = array(
    'query' => drupal_get_destination(),
    'html' => TRUE,
    'attributes' => array(
      'class' => array('compare-toggle', 'use-ajax', $action_class),
      'id' => array($id),
      'rel' => 'nofollow',
    ),
  );
  return l($text, 'compare/toggle/' . $vars['nid'] . '/nojs', $options);
}

