<?php

/**
 * @file
 * Menu builder functions for Node Compare.
 */

namespace Drupal\node_compare\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\HttpFoundation\Request;

class NodeCompareController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  
  public function content() {
    return [
      '#theme' => 'node_compare_display',
    ];
  }
  
   /**
  * Update session when handling the nodes selected for comparison.
  *
  *  NEW CODE
  */
  
  function node_compare_sess_update($type, $nid, $title) {
    $session = \Drupal::request()->getSession();
    #$session = \Drupal::service('user.private_tempstore')->get('node_compare');
    if (isset($session) && $session->get('type') == $type) {
      $limit = (int) \Drupal::state()->get('node_compare_items_limit', 0);
      $node_ids = $session->get('nids');
      if (isset($node_ids[$nid])){
        $session->remove('nids');
        return TRUE;
      }
      elseif ($limit && (count($session->get('nids')) >= $limit)) {
        drupal_set_message(t('Sorry, but you can not compare more than %items_limit items.', array('%items_limit' => $limit)), 'error');
        return FALSE;
      }
    }
    else {

      $session->set('type', $type);
      $session->set('nids', array());
    }
    $node_ids[$nid] = $title; 
    $session->set('nids', $node_ids);
    return TRUE;
  }
  
  /**
  * Add/remove nodes to compare.
  */
  
  function node_compare_ajax_handler($node, $clear = FALSE, Request $request) {
    $node_compare_request = &drupal_static('node_compare_request');
    $updated = $clear ? $this->node_compare_sess_clear() : $this->node_compare_sess_update($node->getType(), $node->id(), $node->getTitle());
    // Checks ajax mode.
    $is_ajax = $request->isXmlHttpRequest();
    if ($is_ajax) {
      $response = new AjaxResponse;
      if ($updated) {
        $response->addCommand(new HtmlCommand('#node-compare-items', $this->theme_node_compare_block_content()));
        #$commands[] = ajax_command_html('#node-compare-items', theme('node_compare_block_content'));
        if ($clear) {
          error_log('clear');
          $response->addCommand(new HtmlCommand('node_compare_clear'));
          #$commands[] = array(
          #  'command' => 'node_compare_clear',
          #  'text' => \Drupal::state()->get('node_compare_text_add'),
          #);
        }
        else {
          $response->addCommand(new ReplaceCommand('#compare-toggle-' . $node->id(), $this->theme_node_compare_toggle_link($node->id())));
          #$commands[] = ajax_command_replace('#compare-toggle-' . $node->nid, theme('node_compare_toggle_link', array('nid' => $node->nid)));
        }
      }
      
      #$page = array('#type' => 'ajax', '#commands' => $commands);
      $node_compare_request = TRUE;
      #ajax_deliver($page);
      
      return $response;
    }
    // If JS disabled, then redirect the user back to the homepage (for now).
    else {
      return $this->redirect('<front>');
    }
  }
  
  
  
  
  /**
  * Generates a page with a comparative table.
  */
  
  function node_compare_page () {
    $nids = func_get_args();
    $type = array_shift($nids);

    if (($nids_count = count($nids)) && $nids_count > 1) {
      global $user;
      $user_roles = implode('/', array_keys($user->roles));
      $cid = 'node_compare:' . $user_roles . '/' . $type . '/' . implode('/', $nids);
      $output = FALSE;
  
      if ($cache = cache_get($cid, 'cache_page')) {
        $output = $cache->data;
      }
      else {
        $limit = variable_get('node_compare_items_limit', 0);
        // Checking for limit and existence of the type variable.
        if (isset($type) && (!$limit || $nids_count <= $limit)) {
          $header = array();
          foreach ($nodes = node_load_multiple($nids, array(), TRUE) as $node) {
            if (!node_access('view', $node)) {
              return MENU_ACCESS_DENIED;
            }
            if ($node->type == $type) {
              $link_options = array('attributes' => array(
                  'title' => $node->title,
                  'class' => array('compare-item'),
                ),
              );
              $header[$node->nid] = array('data' => l($node->title, 'node/' . $node->nid, $link_options), 'class' => 'item-title');
            }
          }
          if (count($header) == $nids_count) {
            $fields = variable_get('node_compare_type_' . $type, array());
            $rows = array();
  
            foreach ($fields as $field_name) {
              $field_not_empty = FALSE;
              if ($instance = field_info_instance('node', $field_name, $type)) {
                $display = isset($instance['display']['node_compare']) ? $instance['display']['node_compare'] : $instance['display']['default'];
  
                $label_classes = array();
                $label_classes[] = 'compare-field-label';
  
                if ($display['label'] == 'hidden') {
                  $instance['label'] = '&nbsp;';
                  $label_classes[] = 'hidden';
                }
                // Prepare translated options if using the i18n_field module.
                elseif (module_exists('i18n_field')) {
                  $instance['label'] = i18n_field_translate_property($instance, 'label');
                }
  
                $display['label'] = 'hidden';
                $row = array(array('data' => $instance['label'], 'class' => implode(' ', $label_classes)));
  
                foreach (array_keys($header) as $nid) {
                  $field = field_view_field('node', $nodes[$nid], $field_name, $display);
                  if ($field) {
                    $row[] = render($field);
                    $field_not_empty = TRUE;
                  }
                  else {
                    $row[] = variable_get('node_compare_empty_field', '&nbsp;');
                  }
                }
              }
              if ($field_not_empty) {
                $rows[$display['weight']] = array('data' => $row, 'class' => array('compare-field-row', $field_name));
              }
            }
            array_unshift($header, array(
                'data' => variable_get('node_compare_labels_header', '&nbsp;'),
                'class' => 'properties-title',
              ));
            if ($rows) {
              ksort($rows);
              $output = array(
                '#theme' => 'table',
                '#header' => $header,
                '#rows' => $rows,
              );
              cache_set($cid, $output, 'cache_page', CACHE_TEMPORARY);
            }
          }
          else {
            drupal_set_message(t('One or more items that you want to compare not exist. Perhaps they were removed from the site after you\'ve marked them for comparison.'), 'warning');
          }
        }
      }
      if ($output) {
        return theme('node_compare_comparison_page', array('comparison_table' => $output));
      }
    }
  
    return node_compare_me();
    
  }
  
  
  
  
  /**
  * Processing for nodes selected for comparison by the current user.
  */
  
  function node_compare_me() {
    if (isset($_SESSION['node_compare']['type'], $_SESSION['node_compare']['nids']) && (count($_SESSION['node_compare']['nids']) > 1)) {
      $sess = $_SESSION['node_compare'];
  
      $nids = array_keys($sess['nids']);
      $nids = '/' . implode('/', $nids);
      $url = 'compare/type/' . $sess['type'] . $nids;
      if (variable_get('node_compare_show_history', FALSE)) {
        $_SESSION['node_compare_history'][time()] = $url;
        unset($_SESSION['node_compare']);
      }
      menu_set_active_item($url);
      return menu_execute_active_handler(NULL, FALSE);
    }
    return t('At the moment you are not selected items to compare.');
  }
  
  
  
  function node_compare_sess_clear() {
    $session = \Drupal::service('user.private_tempstore')->get('node_compare');
    
    if ($session) {
      $session->destroy();
      return TRUE;
    }
    return FALSE;
  } 
  
  
  /**
  * Theming a link to add/remove nodes for compares.
  */
  
  function theme_node_compare_toggle_link($entity, $block = NULL) {
    $id = 'compare-toggle-' . $entity;
    #$node_added = isset($_SESSION['node_compare']['nids'][$entity]);
    $session = \Drupal::request()->getSession();
    #$session = \Drupal::service('user.private_tempstore')->get('node_compare');
    $sess_nids = $session->get('nids');
    $node_added = isset($sess_nids[$entity]);
    $action_class = '';
    $remove_t = \Drupal::state()->get('node_compare_text_remove', 'Remove from comparison');
  
    if ($block) {
      $id .= '-block';
      $path = 'http://127.0.0.1:4568/drupal8test/web/modules/custom/node_compare/img/message-16-error.png';
      $text = 'Remove';
      #$text = '<img title="' . $remove_t . '" src="' . $path . '">';
    }
    else {
      $text = $node_added ? $remove_t : \Drupal::state()->get('node_compare_text_add', 'Add to compare');
      $action_class = $node_added ? 'remove' : 'add';
    }
    $options = array(
      'query' => drupal_get_destination(),
      #'query' => \Drupal::service('redirect.destination')->getAsArray(),
      #'fragment' => '/nojs',
      'html' => TRUE,
      'attributes' => array(
        'class' => array('compare-toggle', 'use-ajax', $action_class),
        'id' => array($id),
        'rel' => 'nofollow',
      ),
    );
    
    #$url = Url::fromRoute('node_compare.toggle', [], $options);
    $url = Url::fromRoute('node_compare.toggle', array('node' => $entity), $options);
    $link = Link::fromTextAndUrl($text, $url)->toString();
    
    return $link;
  
  }

   
   /**
   * Theming a block content.
   */
  
 function theme_node_compare_block_content($vars) {
    error_log('PLEASE DO NOT');
    $output = '';
    $session = \Drupal::request()->getSession();
    #$session = \Drupal::service('user.private_tempstore')->get('node_compare');
    $sess_nids = $session->get('nids');
    $sess_history = $session->get('node_compare_history');
    if (isset($sess_nids)) {
      $sess = $session->get('nids');
      $rows = array();
      foreach ($sess as $nid => $title) {
        $rows[] = array($title, NodeCompareController::theme_node_compare_toggle_link($nid, $block = TRUE)); 
      }
      if (count($sess) > 1) {
        $options = array(
          'query' => \Drupal::service('redirect.destination')->getAsArray(),
          'attributes' => array(
            'class' => array('compare-block-links'),
          ),
        );
        $links = array();
        $url = Url::fromUri('internal:/compare/me');
        $url->setOptions($options);
        $links[] = Link::fromTextAndUrl('Compare Selected', $url)->toString();
        $options['query'] = \Drupal::service('redirect.destination')->getAsArray();
        $options['attributes']['class'][] = 'use-ajax';
        $nojs_url = Url::fromUri('internal:/compare/clear/nojs');
        $nojs_url->setOptions($options);
        $links[] = Link::fromTextAndUrl('Clear', $nojs_url)->toString();
        #l(t('Clear'), 'compare/clear/nojs', $options);
        $rows[] = $links;
      }
      $elements = array('#type' => 'table', '#header' => NULL, '#rows' => $rows);
      $output = \Drupal::service('renderer')->render($elements);
      #$output = theme('table', array('header' => NULL, 'rows' => $rows));
    }
    
    if (isset($sess_history)) {
      $items = array();
      foreach ($sess_history as $date => $link) {
        $items[] = l(format_date($date), $link);
      }
      $output .= \Drupal::service('renderer')->render(array('#theme' => 'item-list', '#title' => t('Your recent comparisons:')));
      #theme('item_list', array('items' => $items, 'title' => t('Your recent comparisons:')));
    }
    return $output;
  } 

}

 