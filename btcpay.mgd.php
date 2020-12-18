<?php

/**
 * The record will be automatically inserted, updated, or deleted from the
 * database as appropriate. For more details, see "hook_civicrm_managed" at:
 * http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference
 *
 */
return array(
  0 => array(
    'name' => 'Btcpay',
    'entity' => 'payment_processor_type',
    'params' => array(
      'version' => 3,
      'title' => 'Btcpay',
      'name' => 'btcpay',
      'description' => 'BTCPay Payment Processor',
      'user_name_label' => 'API Key',
      'password_label' => 'Private Key decryption password',
      'signature_label' => 'Pairing Token',
      'class_name' => 'Payment_Btcpay',
      'url_site_default' => 'https://testnet.demo.btcpayserver.org/',    // to be replaced with url of self-hosted btcpay server url during payment processor creation
      'url_site_test_default' => 'https://testnet.demo.btcpayserver.org/',
      'is_recur' => 0,
      'billing_mode' => 1,
    ),
  )
);

