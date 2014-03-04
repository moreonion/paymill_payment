<?php

namespace Drupal\paymill_payment;

class CommonController extends \PaymentMethodController {
  public $controller_data_defaults = array(
    'api_key'       => '',
    'unite_api_key' => '',
  );

  public function __construct() {
    $this->payment_configuration_form_elements_callback = 'payment_forms_method_form';
    $this->payment_method_configuration_form_elements_callback = '\Drupal\paymill_payment\configuration_form';
  }

  /**
   * Helper for entity_load().
   */
  public static function load($entities) {
    $pmids = array();
    foreach ($entities as $method) {
      if ($method->controller instanceof CommonController) {
        $pmids[] = $method->pmid;
      }
    }
    if ($pmids) {
      $query = db_select('paymill_payment_payment_method_controller', 'controller')
        ->fields('controller')
        ->condition('pmid', $pmids);
      $result = $query->execute();
      while ($data = $result->fetchAssoc()) {
        $method = $entities[$data['pmid']];
        unset($data['pmid']);
        $method->controller_data = (array) $data;
        $method->controller_data += $method->controller->controller_data_defaults;
      }
    }
  }

  /**
   * Helper for entity_insert().
   */
  public function insert($method) {
    $method->controller_data += $this->controller_data_defaults;

    $query = db_insert('paymill_payment_payment_method_controller');
    $values = array_merge($method->controller_data, array('pmid' => $method->pmid));
    $query->fields($values);
    $query->execute();
  }

  /**
   * Helper for entity_update().
   */
  public function update($method) {
    $query = db_update('paymill_payment_payment_method_controller');
    $values = array_merge($method->controller_data, array('pmid' => $method->pmid));
    $query->fields($values);
    $query->condition('pmid', $method->pmid);
    $query->execute();
  }

  /**
   * Helper for entity_delete().
   */
  public function delete($method) {
    db_delete('paymill_payment_payment_method_controller')
      ->condition('pmid', $method->pmid)
      ->execute();
  }

}

/* Implements PaymentMethodController::payment_method_configuration_form_elements_callback().
 *
 * @return array
 *   A Drupal form.
 */
function configuration_form(array $form, array &$form_state) {
  $controller_data = $form_state['payment_method']->controller_data;

  $form['api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Paymill API key'),
    '#description' => t('Available from My Account / Settings / API keys on paymill.com'),
    '#required' => true,
    '#default_value' => isset($controller_data['api_key']) ? $controller_data['api_key'] : '',
  );

  return $form;
}

/**
 * Implements form validate callback for
 * \paymill_payment\configuration_form().
 */
function configuration_form_validate(array $element, array &$form_state) {
  $values = drupal_array_get_nested_value($form_state['values'], $element['#parents']);
  dpm($values['api_key']);
  $form_state['payment_method']->controller_data['api_key'] = $values['api_key'];
}