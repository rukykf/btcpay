<?php

function civicrm_api3_btcpay_createkeys($params) {
  $result = CRM_Btcpay_Keys::createNewKeys($params['payment_processor_id']);
  return civicrm_api3_create_success(['result' => $result], $params, 'Btcpay', 'createkeys');
}

function _civicrm_api3_btcpay_createkeys_spec(&$spec) {
  $spec['payment_processor_id']['api.required'] = 1;
  $spec['payment_processor_id']['title'] = 'Payment Processor ID';
  $spec['payment_processor_id']['description'] = 'The Payment Processor ID';
  $spec['payment_processor_id']['type'] = CRM_Utils_Type::T_INT;
}

/**
 * @param $params
 *
 * @return array
 * @throws \CRM_Core_Exception
 */
function civicrm_api3_btcpay_pair($params) {
  $pairingToken = CRM_Btcpay_Keys::pair($params['payment_processor_id'], $params['pairingkey'], $params['label']);
  return civicrm_api3_create_success(['token' => $pairingToken], $params, 'Btcpay', 'pair');
}

function _civicrm_api3_btcpay_pair_spec(&$spec) {
  $spec['pairingkey']['api.required'] = 1;
  $spec['pairingkey']['title'] = 'Pairing key from your BTCPay server';
  $spec['pairingkey']['type'] = CRM_Utils_Type::T_STRING;
  $spec['payment_processor_id']['api.required'] = 1;
  $spec['payment_processor_id']['title'] = 'Payment Processor ID';
  $spec['payment_processor_id']['description'] = 'The Payment Processor ID';
  $spec['payment_processor_id']['type'] = CRM_Utils_Type::T_INT;
  $spec['label']['api.required'] = 1;
  $spec['label']['title'] = 'Label from your BTCPay server';
  $spec['label']['type'] = CRM_Utils_Type::T_STRING;
}

/**
 * Use this to "fix" a partially installed extension if the old btcpay
 * extension was partially uninstalled. It's safe to run multiple times.
 *
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_btcpay_checkinstall($params) {
  $result = CRM_Core_Payment_Btcpay::createPaymentInstrument(['name' => 'Bitcoin']);

  return civicrm_api3_create_success(['payment_instrument_id' => $result], $params, 'Btcpay', 'checkinstall');
}

function _civicrm_api3_btcpay_checkinstall_spec(&$spec) {
}
