<?php

use CRM_Btcpay_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;
use Civi\Test\Api3TestTrait;

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

  use Api3TestTrait;

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

  // this test assumes that there is only one Btcpay payment processor and it attempts to retrieve that
  // using CiviCRM's getsingle action. If there are more than one Btcpay payment processors then this will not work

  public function testBtcpayGeneratesBtcpayInvoiceOnContributionPage() {
    $paymentProcessor = $this->getBtcpayPaymentProcessor();
    $contactInfo = $this->createDemoContact();

    $form = new CRM_Contribute_Form_Contribution();
    $form->_mode = 'Live';

    $contribution_params = [
      'total_amount' => 10.00,
      'financial_type_id' => 2,
      'contact_id' => $contactInfo['id'],
      'contribution_status_id' => 2,
      'payment_instrument_id' => 6,
      'payment_processor_id' => $paymentProcessor["id"],
      'currency' => 'USD',
      'source' => 'Btcpay Server Bitcoin',
    ];

    $form->testSubmit($contribution_params, CRM_Core_Action::ADD);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $contactInfo['id'],
      'contribution_status_id' => 'Pending',
    ]);

    $this->assertNotNull($contribution['trxn_id']);
  }

  public function testBtcpaySetsParticipantStatusToPendingAfterSubmittingEventRegistrationForm(){
    $paymentProcessor = $this->getBtcpayPaymentProcessor();
    $contactInfo = $this->createDemoContact();

    $form = new CRM_Event_Form_Registration();
  }

  public function testBtcpaySetsContributionStatusToPendingAfterSubmittingEventRegistrationForm(){}

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
    $paymentProcessor = $this->getBtcpayPaymentProcessor();

    // just get the oldest pending bitcoin contribution
    $params = [
      'sequential' => 1,
      'payment_instrument_id' => "Bitcoin",
      'contribution_status_id' => "Pending",
    ];
    $contributionsData = $this->callAPISuccess("Contribution", "get", $params);

    $oldestContribution = $contributionsData["values"][0];

    $this->assertEquals(2, $oldestContribution['contribution_status_id']); //contribution is Pending

    $ipnData = $this->getIPNData($oldestContribution['trxn_id'], $paymentProcessor["id"]);

    $ipn = new CRM_Core_Payment_BtcpayIPN($ipnData);
    $output = $ipn->main();

    $this->assertTrue($output);

    // check that the contribution's status was updated to completed
    // should work so long as the invoice has been paid and confirmed on the btcpay server
    $oldestContribution = civicrm_api3('Contribution', 'getsingle', [
      'id' => $oldestContribution['id'],
    ]);
    $this->assertEquals(1, $oldestContribution['contribution_status_id']); // contribution is Completed
  }

  public function testBtcpayIPNUpdatesEventParticipantStatusAfterPayment(){}

  public function testBtcpayIPNUpdatesEventContributionStatusAfterPayment(){}

  private function getBtcpayPaymentProcessor() {
    $params = [
      'is_test' => 0,
      'payment_processor_type_id' => "btcpay",
    ];

    return $this->callAPISuccessGetSingle("PaymentProcessor", $params);
  }

  private function createDemoContact() {
    $params = [
      'sequential' => 1,
      'first_name' => "some",
      'last_name' => "person",
      'contact_type' => "Individual",
    ];

    return $this->callAPISuccess('contact', 'create', $params);
  }

  private function getIPNData($invoiceId, $paymentProcessorId) {
    $ipnData = new stdClass();
    $ipnData->id = $invoiceId;
    $ipnData->paymentProcessorId = $paymentProcessorId;
    $ipnData->status = 'complete';
    return $ipnData;
  }

}

