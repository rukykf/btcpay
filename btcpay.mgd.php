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

