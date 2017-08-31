<?php

/**
 * @file
 * Menu builder functions for Node Compare.
 */

namespace Drupal\node_compare\Controller;

use Drupal\Core\Controller\ControllerBase;

class NodeCompareController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->t('Hello, World!'),
    );
  }
  
  /**
  * Add/remove nodes to compare.
  */
  
  function node_compare_ajax_handler () {
    
  }
  
  /**
  * Generates a page with a comparative table.
  */
  
  function node_compare_page () {
    
  }
  
  /**
  * Processing for nodes selected for comparison by the current user.
  */
  
  function node_compare_me() {
    
  }

}