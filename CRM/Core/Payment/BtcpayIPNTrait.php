<?php

use Civi\Api4\Contribution;

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

trait CRM_Core_Payment_BtcpayIPNTrait {

  /**
   * @var array Payment processor
   */
  private $_paymentProcessor;

  /**
   * Get the payment processor
   *   The $_GET['processor_id'] value is set by CRM_Core_Payment::handlePaymentMethod.
   */
  protected function getPaymentProcessor() {
    $paymentProcessorId = (int) CRM_Utils_Array::value('processor_id', $_GET);
    if (empty($paymentProcessorId)) {
      $this->exception('Failed to get payment processor id');
    }

    try {
      $this->_paymentProcessor = \Civi\Payment\System::singleton()->getById($paymentProcessorId)->getPaymentProcessor();
    }
    catch(Exception $e) {
      $this->exception('Failed to get payment processor');
    }
  }

  /**
   * Mark a contribution as cancelled and update related entities
   *
   * @param array $params [ 'id' -> contribution_id, 'payment_processor_id' -> payment_processor_id]
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function canceltransaction($params) {
    return $this->incompletetransaction($params, 'cancel');
  }

  /**
   * Mark a contribution as failed and update related entities
   *
   * @param array $params [ 'id' -> contribution_id, 'payment_processor_id' -> payment_processor_id]
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function failtransaction($params) {
    return $this->incompletetransaction($params, 'fail');
  }

  /**
   * Handler for failtransaction and canceltransaction - do not call directly
   *
   * @param array $params
   * @param string $mode
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function incompletetransaction($params, $mode) {
    $requiredParams = ['id', 'payment_processor_id'];
    foreach ($requiredParams as $required) {
      if (!isset($params[$required])) {
        $this->exception('canceltransaction: Missing mandatory parameter: ' . $required);
      }
    }

    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $params['id'];
    if (!$contribution->find(TRUE)) {
      throw new CRM_Core_Exception('A valid contribution ID is required', 'invalid_data');
    }

    switch ($mode) {
      case 'cancel':
        Contribution::update(FALSE)->setValues([
          'cancel_date' => 'now',
          'contribution_status_id:name' => 'Cancelled',
        ])->addWhere('id', '=', $contribution->id)->execute();
        return TRUE;

      case 'fail':
        Contribution::update(FALSE)->setValues([
          'cancel_date' => 'now',
          'contribution_status_id:name' => 'Failed',
        ])->addWhere('id', '=', $contribution->id)->execute();
        Civi::log()->debug("Setting contribution status to Failed");
        return TRUE;

      default:
        throw new CRM_Core_Exception('Unknown incomplete transaction type: ' . $mode);
    }

  }

}
