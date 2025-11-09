<?php

/**
 * Package: BTCPay (CiviCRM Extension)
 * Copyright (C) 2020, Kofi Oghenerukevwe <rukykf@gmail.com>
 * Licensed under the GNU Affero Public License 3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, version 3 of the license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
**/

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
      Civi::log()
        ->debug("====================================MODIFYING EVENT CONFIRMATION FORM");
    case 'CRM_Contribute_Form_Contribution_Confirm':
      Civi::log()
        ->debug("====================================MODIFYING CONTRIBUTION CONFIRMATION FORM");
      $form->assign('btcpayServerUrl', $paymentProcessor["url_site"]);

      Civi::resources()->add(
        [
          'template' => 'Btcpaycontribution-confirm-billing-block.tpl',
          'region' => 'form-top',
        ]
      );
      break;

    case 'CRM_Event_Form_Registration_ThankYou':
      Civi::log()
        ->debug("====================================MODIFYING EVENT THANK YOU FORM");
    case 'CRM_Contribute_Form_Contribution_ThankYou':
      Civi::log()
        ->debug("====================================MODIFYING CONTRIBUTION THANK YOU FORM");

      if (!isset($billingBlockRegion)) {
        $billingBlockRegion = 'contribution-thankyou-billing-block';
      }
      // Contribution Thankyou form
      // Add the Btcpay invoice handling
      $trxnId = isset($form->_trxnId) ? $form->_trxnId : NULL;
      if (empty($trxnId) && $formName == "CRM_Contribute_Form_Contribution_ThankYou") {
        $contributionParams = [
          'contact_id' => $form->_contactID,
          'total_amount' => $form->_amount,
          'contribution_test' => '',
          'options' => ['limit' => 1, 'sort' => ['id DESC']],
        ];
        $contribution = civicrm_api3('Contribution', 'get', $contributionParams);
        $trxnId = CRM_Utils_Array::first($contribution['values'])['trxn_id'];
      }

      $client = new CRM_Btcpay_Client($form->_paymentProcessor);
      $paymentInfo = $client->getClient()->getInvoicePaymentInfo($trxnId);


      $form->assign('btcpayTrxnId', $trxnId);
      $form->assign('btcpayServerUrl', $paymentProcessor["url_site"]);

      $form->assign('btcpayPrice', $paymentInfo['price']);
      $form->assign('btcpayCurrency', $paymentInfo['currency']);
      $form->assign('btcpayCryptoInfo', $paymentInfo['cryptoInfo']);
      $form->assign('btcpayPaymentUrl', $paymentInfo["url"]);

      Civi::resources()->add(
        [
          'template' => 'Btcpaycontribution-thankyou-billing-block.tpl',
          'region' => 'form-top',
        ]
      );
      break;
  }
}

/**
 * Implements hook_civicrm_postProcess().
 */
function btcpay_civicrm_postProcess($formName, &$form) {
  switch ($formName) {
    case 'CRM_Event_Form_Registration_Confirm':
      Civi::log()
        ->debug("====================================UPDATING PARTICIPANT AND CONTRIBUTION STATUS FOR EVENT\n");

      // update the Contribution and Participants' status for the event to Pending
      $contributionId = $form->_values["contributionId"] ?? NULL;
      $participantParams = $form->_values["participant"] ?? NULL;


      $mainParticipant = $participantParams;
      $participants = [];

      if (isset($participantParams["participant_registered_by_id"])) {
        // get the main participant who did the registering
        $mainParticipant = civicrm_api3("Participant", "getsingle", [
          "id" => $participantParams["participant_registered_by_id"],
        ]);

        // get participants that were registered by the main participant
        $result = civicrm_api3('Participant', 'get', [
          'sequential' => 1,
          'registered_by_id' => $mainParticipant["id"],
        ]);
        $participants = $result["values"];
      }

      $participants[] = $mainParticipant;

      // update all the participants' status to pending - incomplete transaction
      foreach ($participants as $participant) {
        $participantParams = [
          "id" => $participant["id"],
          "status_id" => 6,
        ];
        civicrm_api3('Participant', 'create', $participantParams);
      }


      // delete the former contribution and create a new pending one
      // the api won't allow me directly update the status of the contribution, this is the only way to do it.

      $contributionParams = [
        "id" => $contributionId,
      ];

      $contribution = civicrm_api3('Contribution', 'getsingle', $contributionParams);

      civicrm_api3("Contribution", "delete", $contributionParams);

      Civi::log()
        ->debug("\n" . "CONTRIBUTION ARRAY" . "\n\n" . print_r($contribution, TRUE));

      $newContribution = $contribution;
      $newContribution["contribution_status_id"] = 2;

      unset($newContribution["id"]);
      unset($newContribution["contribution_id"]);
      unset($newContribution["contribution_status"]);
      unset($newContribution["amount_level"]);

      $newContribution = civicrm_api3("Contribution", "create", $newContribution);

      $result = civicrm_api3('ParticipantPayment', 'create', [
        'participant_id' => $mainParticipant["id"],
        'contribution_id' => $newContribution["id"],
      ]);

  }
}
