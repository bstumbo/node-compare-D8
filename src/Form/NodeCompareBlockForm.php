<?php

namespace Drupal\node_compare\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Lorem Ipsum block form
 */
class NodeCompareBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_compare_block_form';
  }
  
   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
   
    $form['node_compare_show_history'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show recent comparisons'),
      '#description' => t('Display the recent comparisons list in the comparison block.'),
    );

    return $form;
  }
  
   /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'node_compare.form',
      array(
        'node_compare_show_history' => $form_state->getValue('node_compare_show_history'),
      )
    );
  }

}