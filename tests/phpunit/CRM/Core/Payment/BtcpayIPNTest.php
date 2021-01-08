<?php

use CRM_Btcpay_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - The global variable $_CV has some properties which may be useful, such
 * as:
 *    CMS_URL, ADMIN_USER, ADMIN_PASS, ADMIN_EMAIL, DEMO_USER, DEMO_PASS,
 * DEMO_EMAIL.
 *  - To spawn a new CiviCRM thread and execute an API call or PHP code, use
 * cv(), e.g. cv('api system.flush');
 *      $data = cv('eval "return Civi::settings()->get(\'foobar\')"');
 *      $dashboardUrl = cv('url civicrm/dashboard');
 *  - This template uses the most generic base-class, but you may want to use a
 * more powerful base class, such as \PHPUnit_Extensions_SeleniumTestCase or
 *    \PHPUnit_Extensions_Selenium2TestCase.
 *    See also: https://phpunit.de/manual/4.8/en/selenium.html
 *
 * @group e2e
 * @see cv
 */
class CRM_Core_Payment_BtcpayIPNTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface {

  public static function setUpBeforeClass() {
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest

    // Example: Install this extension. Don't care about anything else.
    \Civi\Test::e2e()->apply();

    // Example: Uninstall all extensions except this one.
    // \Civi\Test::e2e()->uninstall('*')->installMe(__DIR__)->apply();

    // Example: Install only core civicrm extensions.
    // \Civi\Test::e2e()->uninstall('*')->install('org.civicrm.*')->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testBtcpayGeneratesBtcpayInvoiceOnContributionPage() {
  }

  /** This test works by retrieving the first pending btcpay contribution it finds
   * and triggering the IPN script using the transaction id of that
   * contribution
   *
   * BEFORE RUNNING THE TEST:
   * ensure there is at least one pending btcpay contribution (using the live
   * btcpay payment processor) that would have finished processing on your
   * btcpay server host at this point (ideally, should have been processed 2 -
   * 4 hours ago)
   *
   **/
  public function testBtcpayIPNUpdatesContributionStatusAfterPayment() {

  }

}

