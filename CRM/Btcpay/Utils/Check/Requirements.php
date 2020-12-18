<?php
/**
 * Description
 *
 * @package ${NAMESPACE}
 * @since 1.0.0
 * @author Kofi Oghenerukevwe Henrietta
 * @link http://github.com/rukykf
 * @license GPL-2.0+
 *
 */

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
