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

class CRM_Core_Payment_BtcpayIPN extends CRM_Core_Payment_BaseIPN
{

  use CRM_Core_Payment_BtcpayIPNTrait;

  /**
   * @var CRM_Btcpay_Client The Btcpay client object
   */
  private $_client = NULL;

  /**
   * @var \BTCPayServer\Invoice
   */
  private $_invoice = NULL;

  /**
   * CRM_Core_Payment_BtcpayIPN constructor.
   *
   * @param array $ipnData
   * @param bool $verify
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($ipnData)
  {
    $this->setInputParameters($ipnData);
    parent::__construct();
  }

  public function setInputParameters($ipnData)
  {
    // Get the payment processor
    if (!isset($ipnData->paymentProcessorId)) {
      $this->getPaymentProcessor();
    } else {
      $this->getPaymentProcessorForUnitTest($ipnData->paymentProcessorId);
    }


    // Get the btcpay client
    $this->_client = new CRM_Btcpay_Client($this->_paymentProcessor);
    $client = $this->_client->getClient();

    // Now fetch the invoice from BTCPay
    // This is needed, since the IPN does not contain any authentication
    $invoice = $client->getInvoice($ipnData->id);
    $this->_invoice = $invoice;

    Civi::log()->debug(print_r($invoice, TRUE));

    // FIXME: this is for debug, we could remove it...
    $invoiceId = $invoice->getId();
    $invoiceStatus = $invoice->getStatus();
    $invoiceExceptionStatus = $invoice->getExceptionStatus();
    $invoicePrice = $invoice->getPrice();
    Civi::log()
      ->debug("IPN received for Btcpay invoice " . $invoiceId . " . Status = " . $invoiceStatus . " / exceptionStatus = " . $invoiceExceptionStatus . " Price = " . $invoicePrice . "\n");
    Civi::log()->debug("Raw IPN data: " . print_r($ipnData, TRUE));
  }

  /**
   * Main handler for btcpay IPN callback
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function main()
  {
    // First we receive an IPN with status "paid" - contribution remains pending - how do we indicate we received "paid"?
    // Then we receive an IPN with status "confirmed" - we set contribution = completed.

    switch ($this->_invoice->getStatus()) {
      case \BTCPayServer\Invoice::STATUS_NEW:
        // We don't do anything in this state
        return TRUE;

      case \BTCPayServer\Invoice::STATUS_EXPIRED:
        // Mark as cancelled
        $this->canceltransaction([
          'id' => $this->getContributionId(),
          $this->_paymentProcessor['id'],
        ]);
        break;

      case \BTCPayServer\Invoice::STATUS_INVALID:
        // Mark as failed
        $this->failtransaction([
          'id' => $this->getContributionId(),
          $this->_paymentProcessor['id'],
        ]);
        break;

      case \BTCPayServer\Invoice::STATUS_PAID:
        // Remain in pending status
        // FIXME: Should we record the paid status?
        return TRUE;

      case \BTCPayServer\Invoice::STATUS_CONFIRMED:
        // Mark payment as completed
        $contributionId = $this->getContributionId();

        $result = civicrm_api3('Contribution', 'completetransaction', [
          'id' => $contributionId,
          'trxn_date' => $this::$_now,
          'is_email_receipt' => 0,
        ]);

        // send a confirmation email to the contact who made the contribution
        $participantPayment = civicrm_api3('Payment', 'getsingle', [
          'contribution_id' => $contributionId,
        ]);

        civicrm_api3('Payment', 'sendconfirmation', [
          'id' => $participantPayment["id"],
        ]);

        return TRUE;

      case \BTCPayServer\Invoice::STATUS_COMPLETE:
        // Check if the contribution was already completed and if not, complete it
        // this code is here to make it easier to run e2e tests which might get triggered
        // when an invoice has already been marked "complete" instead of "confirmed"

        $contribution = civicrm_api3('Contribution', 'getsingle', [
          'id' => $this->getContributionId(),
        ]);

        if ($contribution['contribution_status_id'] !== 1) {
          civicrm_api3('Contribution', 'completetransaction', [
            'id' => $this->getContributionId(),
            'trxn_date' => $this::$_now,
            'is_email_receipt' => 0,
          ]);
        }
        return TRUE;

    }
  }

  /**
   * @return int Contribution ID
   */
  private function getContributionId()
  {
    try {
      return civicrm_api3('Contribution', 'getvalue', [
        'return' => "id",
        'trxn_id' => $this->_invoice->getId(),
        'contribution_test' => $this->_paymentProcessor['is_test'],
      ]);
    } catch (Exception $e) {
      $this->exception('Could not find contribution ID for invoice ' . $this->_invoice->getId());
    }
  }


  /**
   * When running this BtcpayIPN script from a unit test, the payment processor
   * Id will be provided as part of the $ipnData parameter as opposed to being
   * set by Civi's handle IPN hook
   *
   * @return void
   * @throws \CRM_Core_Exception
   *
   */
  private function getPaymentProcessorForUnitTest($paymentProcessorId)
  {
    $this->_paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', [
      'id' => $paymentProcessorId,
    ]);
  }

  private function exception($message)
  {
    $errorMessage = 'BtcpayIPN Exception: Error: ' . $message;
    Civi::log()->debug($errorMessage);
    http_response_code(400);
    exit(1);
  }

}
