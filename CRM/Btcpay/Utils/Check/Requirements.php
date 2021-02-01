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

use CRM_Btcpay_ExtensionUtil as E;

class CRM_Btcpay_Utils_Check_Requirements {

  /**
   * Checks whether all the requirements for btcpay have been met.
   *
   * @see btcpay_civicrm_check()
   */
  public static function check(&$messages) {
    $requirements = \BTCPayServer\Util\Util::checkRequirements();

    $failedRequirements = [];
    foreach ($requirements as $key => $requirement) {
      if ($requirement !== TRUE) {
        $failedRequirements[] = $requirement;
      }
    }
    if (!empty($failedRequirements)) {
      $messages[] = new CRM_Utils_Check_Message(
        'btcpay_requirements',
        'The btcpay payment processor has missing requirements: ' . implode('<br />', $failedRequirements),
        E::ts('Btcpay - Missing Requirements'),
        \Psr\Log\LogLevel::ERROR,
        'fa-money'
      );
    }
  }

}
