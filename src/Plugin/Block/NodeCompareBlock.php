<?php

namespace Drupal\node_compare\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Node Compare Block.
 *
 * @Block(
 *   id = "node_compare_items",
 *   admin_label = @Translation("Node Compare Block"),
 *   category = @Translation("Node Compare"),
 * )
 */
class NodeCompareBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  
  public function defaultConfiguration() {
    $default_config = \Drupal::config('node_compare.settings');
    return array(
      'node_compare_name' => $default_config->get('node_compare_block.name'),
    );
  }
  
 /* protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'use comparison');
  } */
  
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['node_compare_show_history'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show recent comparisons'),
      '#description' => t('Display the recent comparisons list in the comparison block.'),
      #'#default_value' => $config->get('node_compare_show_history'),
    );

    return $form;
  }
  
   public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['node_compare_show_history'] = $form_state->getValue('node_compare_show_history');
  }
  
  
  public function build() {
    $config = $this->getConfiguration();
    $markup = \Drupal\node_compare\Controller\NodeCompareController::theme_node_compare_block_content();
    return array(
    'subject' => t('Content for comparison'),
    'content' => array(
      '#markup' => $markup, 
      '#cache' => array(
        'max-age' => 0,
      ),
      '#variables' => array(
        'type' => NULL,
        'nids' => array(),
      ),
      '#attached' => array(
        'library' => array(
          'node_compare/node-compare',
        ), 
      ),
      '#prefix' => '<div  id="node-compare-items">',
      '#suffix' => '</div>',
      ),
    ); 
  }
}
  