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

// phpcs:disable
use CRM_Btcpay_ExtensionUtil as E;
// phpcs:enable

class CRM_Core_Payment_Btcpay extends CRM_Core_Payment {

  use CRM_Core_Payment_BtcpayTrait;

  public static $className = 'Payment_Btcpay';

  /**
   * @var CRM_Btcpay_Client The Btcpay client object
   */
  private $_client = NULL;

  /**
   * Constructor
   *
   * @param string $mode
   *   The mode of operation: live or test.
   *
   * @return void
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = E::ts('Btcpay');
    $this->_client = new CRM_Btcpay_Client($this->_paymentProcessor);
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return null|string
   *   The error message if any.
   */
  public function checkConfig() {
    $error = [];

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = E::ts('The decryption password has not been set.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * We can use the btcpay processor on the backend
   *
   * @return bool
   */
  public function supportsBackOffice() {
    return FALSE;
  }

  /**
   * We can edit recurring contributions
   *
   * @return bool
   */
  public function supportsEditRecurringContribution() {
    return FALSE;
  }

  /**
   * We can configure a start date
   *
   * @return bool
   */
  public function supportsFutureRecurStartDate() {
    return FALSE;
  }

  /**
   * Override CRM_Core_Payment function
   *
   * @return array
   */
  public function getPaymentFormFields() {
    // Btcpay loads a payment modal via JS, we don't need any payment fields
    return [];
  }

  /**
   * Return an array of all the details about the fields potentially required
   * for payment fields. Only those determined by getPaymentFormFields will
   * actually be assigned to the form
   *
   * @return array
   *   field metadata
   */
  public function getPaymentFormFieldsMetadata() {
    // Btcpay loads a payment modal via JS, we don't need any payment fields
    return [];
  }

  /**
   * Get form metadata for billing address fields.
   *
   * @param int $billingLocationID
   *
   * @return array
   *    Array of metadata for address fields.
   */
  public function getBillingAddressFieldsMetadata($billingLocationID = NULL) {
    // Btcpay loads a payment modal via JS, we don't need any billing fields - could optionally add some though?
    return [];
  }

  public function getBillingAddressFields($billingLocationID = NULL) {
    // Btcpay loads a payment modal via JS, we don't need any billing fields - could optionally add some though?
    return [];
  }

  /**
   * Process payment
   * Submit a payment using Btcpay's PHP API:
   *
   * Payment processors should set payment_status_id and trxn_id (if available).
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @param string $component
   *
   * @return array
   *   Result array
   *
   * @throws \CRM_Core_Exception
   * @throws \CRM_Core_Exception
   */
  public function doPayment(&$params, $component = 'contribute') {
    Civi::log()->debug(print_r($component, TRUE));
    Civi::log()->debug(print_r($params, TRUE));

    $client = $this->_client->getClient();
    /**
     * This is where we will start to create an Invoice object, make sure to check
     * the InvoiceInterface for methods that you can use.
     */
    $invoice = new \BTCPayServer\Invoice();
    $buyer = new \BTCPayServer\Buyer();
    $buyer->setEmail($this->getBillingEmail($params, $this->getContactId($params)));
    // Add the buyers info to invoice
    $invoice->setBuyer($buyer);
    /**
     * Item is used to keep track of a few things
     */
    $item = new \BTCPayServer\Item();
    $item
      ->setCode(CRM_Utils_Array::value('item_name', $params))
      ->setDescription($params['description'])
      ->setPrice($this->getAmount($params));
    $invoice->setItem($item);

    /* You can configure the exchange rate provider that BTCPayServer uses from your store */

    $invoice->setCurrency(new \BTCPayServer\Currency($this->getCurrency($params)));
    // Configure the rest of the invoice

    $invoice->setOrderId($params['invoiceID']);

    // You will receive IPN's at this URL, should be HTTPS for security purposes!
    $invoice->setNotificationUrl($this->getNotifyUrl());
    /**
     * Updates invoice with new information such as the invoice id and the URL where
     * a customer can view the invoice.
     */
    try {
      $client->createInvoice($invoice);
    } catch (\Exception $e) {
      $msg = "Btcpay doPayment Exception occured: " . $e->getMessage() . PHP_EOL;
      $request = $client->getRequest();
      $response = $client->getResponse();
      $msg .= (string) $request . PHP_EOL . PHP_EOL . PHP_EOL;
      $msg .= (string) $response . PHP_EOL . PHP_EOL;
      Civi::log()->debug($msg);
      throw new Civi\Payment\Exception\PaymentProcessorException($msg);
    }
    Civi::log()
      ->debug('invoice created: ' . $invoice->getId() . '" url: ' . $invoice->getUrl() . ' Verbose details: ' . print_r($invoice, TRUE));

    // For contribution workflow we have a contributionId so we can set parameters directly.
    $newParams['trxn_id'] = $invoice->getId();
    $newParams['payment_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');

    if ($this->getContributionId($params)) {
      $newParams['id'] = $this->getContributionId($params);
      civicrm_api3('Contribution', 'create', $newParams);
      unset($newParams['id']);
    }
    $params = array_merge($params, $newParams);

    return $params;

  }

  /**
   * Default payment instrument validation.
   *
   * Implement the usual Luhn algorithm via a static function in the
   * CRM_Core_Payment_Form if it's a credit card Not a static function, because
   * I need to check for payment_type.
   *
   * @param array $values
   * @param array $errors
   */
  public function validatePaymentInstrument($values, &$errors) {
    // Use $_POST here and not $values - for webform fields are not set in $values, but are in $_POST
    CRM_Core_Form::validateMandatoryFields($this->getMandatoryFields(), $_POST, $errors);
  }

  /**
   * Process incoming payment notification (IPN).
   *
   * @throws \CRM_Core_Exception
   * @throws \CRM_Core_Exception
   */
  public static function handlePaymentNotification() {
    $dataRaw = file_get_contents("php://input");
    $data = json_decode($dataRaw);
    $ipnClass = new CRM_Core_Payment_BtcpayIPN($data);
    if ($ipnClass->main()) {
      //Respond with HTTP 200, so BTCPay knows the IPN has been received correctly
      http_response_code(200);
    }
  }

}
