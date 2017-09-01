<?php

namespace Drupal\node_compare\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\FieldConfig;

class NodeCompareForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_compare_form';
  }
  
    /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('node_compare.settings');
    
    // Limit to # of items to compare
    $form['node_compare_items_limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Max. number of compared items'),
      '#default_value' => $config->get('node_compare.node_compare_items_limit'),
      '#description' => t('The limit on the number of compared items (0 - no limit)'),
      '#size' => 2,
      '#maxlength' => 2,
      '#element_validate' => array('element_validate_integer'),
    );
    // Show History | Boolean
    $form['node_compare_show_history'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show history'),
      '#description' => t('Show links to the pages of previous comparisons'),
      '#default_value' => $config->get('node_compare.node_compare_show_history'),
    );
    // 
    $form['texts'] = array(
      '#type' => 'fieldset',
      '#title' => t('Text labels'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    //
    $form['texts']['node_compare_text_add'] = array(
      '#type' => 'textfield',
      '#title' => t('The text for the link "add to comparison"'),
      '#default_value' => $config->get('node_compare.node_compare_text_add'),
      '#required' => TRUE,
      '#size' => 40,
    );
    //
    $form['texts']['node_compare_only_diff'] = array(
      '#type' => 'textfield',
      '#title' => t('"Show differences only" text (for comp. table)'),
      '#default_value' => $config->get('node_compare.node_compare_only_diff'),
      '#size' => 40,
      '#required' => TRUE,
    );
    //
    $form['texts']['node_compare_empty_field'] = array(
      '#type' => 'textfield',
      '#title' => t('Replacing an empty field'),
      '#description' => t('Replacement for a empty field value (in comp. table).'),
      '#default_value' => $config->get('node_compare.node_compare_empty_field'),
      '#size' => 40,
    );
    //
    $form['texts']['node_compare_labels_header'] = array(
      '#type' => 'textfield',
      '#title' => t('Header for column with a labels'),
      '#description' => t('Replacement for header of column with a labels (in comp. table).'),
      '#default_value' => $config->get('node_compare.node_compare_labels_header'),
      '#size' => 40,
    );
    
    // Pull in all Node types and corresponding fields
    
    if ($types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple()) {
    $form['fields'] = array(
      '#type' => 'fieldset',
      '#title' => t('Types and fields'),
      '#description' => t("Choose separately for each type of node which fields are allowed to compare.<br />Types of nodes for which you do not select any field, are excluded from the comparison."),
      '#collapsible' => TRUE,
    );
    
    /*
     * Comments needed here
     */
      
    foreach ($types as $type) {
      $entityManager = \Drupal::service('entity_field.manager');
      $fields = $entityManager->getFieldDefinitions('node', 'article');
      
    /*
     * Comments needed here
     */
      
      if ($fields) {
        $form['fields'][$type->get('type')] = array(
          '#type' => 'fieldset',
          '#title' => t('Type: @type', array('@type' => $type->get('type'))),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        );
        $field_list = array();
        foreach ($fields as $field) {
          $field_list[$field->getName()] = $field->getName() . ' (' . $field->getName() . ')';
        }
        $form['fields'][$type->get('type')]['node_compare_' . $type->get('type')] = array(
          '#type' => 'checkboxes',
          '#return_value' => 1,
          '#options' => $field_list,
          '#default_value' => $config->get('node_compare.node_compare_' . $type->get('type')),

        );
      } 
    }
  }

   return parent::buildForm($form, $form_state);
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
    $config = $this->config('node_compare.settings');
    
    foreach($form_state->getValue() as $field => $name) {
     $config->set('node_compare.' . $field, $name);
    }
        
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'node_compare.settings',
    ];
  }


}