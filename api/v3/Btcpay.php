<?php

function civicrm_api3_btcpay_createkeys($params) {
  $result = CRM_Bitpay_Keys::createNewKeys($params['payment_processor_id']);
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
  $pairingToken = CRM_Btcpay_Keys::pair($params['payment_processor_id'], $params['pairingkey']);
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
}

