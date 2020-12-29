<?php

require_once 'btcpay.civix.php';
require_once __DIR__ . '/vendor/autoload.php';

// phpcs:disable
use CRM_Btcpay_ExtensionUtil as E;

// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function btcpay_civicrm_config(&$config) {
  _btcpay_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function btcpay_civicrm_xmlMenu(&$files) {
  _btcpay_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function btcpay_civicrm_install() {
  _btcpay_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function btcpay_civicrm_postInstall() {
  CRM_Core_Payment_Btcpay::createPaymentInstrument(['name' => 'Bitcoin']);
  _btcpay_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function btcpay_civicrm_uninstall() {
  _btcpay_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function btcpay_civicrm_enable() {
  _btcpay_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function btcpay_civicrm_disable() {
  _btcpay_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function btcpay_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _btcpay_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function btcpay_civicrm_managed(&$entities) {
  _btcpay_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function btcpay_civicrm_caseTypes(&$caseTypes) {
  _btcpay_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function btcpay_civicrm_angularModules(&$angularModules) {
  _btcpay_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function btcpay_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _btcpay_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function btcpay_civicrm_entityTypes(&$entityTypes) {
  _btcpay_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function btcpay_civicrm_themes(&$themes) {
  _btcpay_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_check().
 */
function btcpay_civicrm_check(&$messages) {
  CRM_Btcpay_Utils_Check_Requirements::check($messages);
}

/**
 * Add {payment_library}.js to forms, for payment processor handling
 * hook_civicrm_alterContent is not called for all forms (eg.
 * CRM_Contribute_Form_Contribution on backend)
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function btcpay_civicrm_buildForm($formName, &$form) {
  if (!isset($form->_paymentProcessor)) {
    return;
  }
  $paymentProcessor = $form->_paymentProcessor;
  if (empty($paymentProcessor['class_name']) || ($paymentProcessor['class_name'] !== 'Payment_Btcpay')) {
    return;
  }

  switch ($formName) {
    case 'CRM_Event_Form_Registration_Confirm':
    case 'CRM_Contribute_Form_Contribution_Confirm':
      Civi::log()->debug("====================================MODIFYING CONFIRMATION FORM");
      // Confirm Contribution (check details and confirm)
      $form->assign('btcpayServerUrl', $paymentProcessor["url_site"]);
      Civi::resources()
        ->addScriptUrl("https://btcserver.btcpay0p.fsf.org/modal/btcpay.js", [
          'region' => 'html-header',
          'weight' => 100,
        ]);
            CRM_Core_Region::instance('contribution-confirm-billing-block')
              ->update('default', ['disabled' => TRUE]);
            CRM_Core_Region::instance('contribution-confirm-billing-block')
              ->add(['template' => 'Btcpaycontribution-confirm-billing-block.tpl']);
      break;

    case 'CRM_Event_Form_Registration_ThankYou':
      $billingBlockRegion = 'event-thankyou-billing-block';
    case 'CRM_Contribute_Form_Contribution_ThankYou':
      if (!isset($billingBlockRegion)) {
        $billingBlockRegion = 'contribution-thankyou-billing-block';
      }
      // Contribution /Event Thankyou form
      // Add the bitpay invoice handling
      $contributionParams = [
        'contact_id' => $form->_contactID,
        'total_amount' => $form->_amount,
        'contribution_test' => '',
        'options' => ['limit' => 1, 'sort' => ['id DESC']],
      ];
      $trxnId = isset($form->trxnId) ? $form->trxnId : NULL;
      if (empty($trxnId)) {
        $contribution = civicrm_api3('Contribution', 'get', $contributionParams);
        $trxnId = CRM_Utils_Array::first($contribution['values'])['trxn_id'];
      }
      $form->assign('btcpayTrxnId', $trxnId);
      $form->assign('btcpayServerUrl', $paymentProcessor["url_site"]);
      Civi::resources()
        ->addScriptUrl("https://btcserver.btcpay0p.fsf.org/modal/btcpay.js", [
          'region' => 'html-header',
          'weight' => 100,
        ]);
      CRM_Core_Region::instance($billingBlockRegion)
        ->update('default', ['disabled' => TRUE]);
      CRM_Core_Region::instance($billingBlockRegion)
        ->add(['template' => 'Btcpaycontribution-thankyou-billing-block.tpl']);
      break;
  }
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function btcpay_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function btcpay_civicrm_navigationMenu(&$menu) {
//  _btcpay_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _btcpay_civix_navigationMenu($menu);
//}
