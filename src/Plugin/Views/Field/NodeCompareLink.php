<?php
 
/**
 * @file
 * Definition of Drupal\node_compare\Plugin\views\field\NodeCompareLink
 */
 
namespace Drupal\node_compare\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
#use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display “Add to Compare” link for nodes..
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_compare_link")
 */
class NodeCompareLink extends FieldPluginBase {
 
  /**
   * @{inheritdoc}
   */
  public function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }
 
  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    if (isset($this->definition['element type'])){
      $options['element type'] = $this->definition['element type'];
    }
 
    return $options;
  }
 
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [];
    $form['node_compare_link'] = array(
      '#title' => $this->t('Add this piece of content'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['node_type'],
      '#options' => $options,
    );
 
    parent::buildOptionsForm($form, $form_state);
  }
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $type = $values->{$this->aliases['type']};
    kint($type);
    #$type = 'article';
    if (\Drupal::state()->get('node_compare_type_' . $type, array())) {
      #return theme('node_compare_toggle_link', array('nid' => $values->{$this->aliases['nid']}));
      return $this->t('Hey, this is a test.');
    }
    return t('Hey, this is a test.'); 
  }
}