<?php

use CRM_Btcpay_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;
use Civi\Test\Api3TestTrait;

/**
 * Test for the Btcpay extension.
 *
 * @group e2e
 * @see cv
 */
class CRM_Core_Payment_BtcpayTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface {

  use Api3TestTrait;

  public static function setUpBeforeClass() {
    \Civi\Test::e2e()->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * BEFORE RUNNING THE TEST
   * Ensure you've created ONLY one live payment processor of type Btcpay on the
   * local installation of CiviCRM
   * (the installation this extension is installed on).
   *
   * This code will throw an exception if it finds anything but exactly 1
   * Btcpay payment processors.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
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


  /**
   * This test works by retrieving the first pending btcpay contribution it
   * finds and triggering the IPN script using the transaction id of that
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

  /**
   * BEFORE RUNNING TEST
   * Ensure there is at least one event in the local installation of CiviCRM
   * (the installation this extension is installed on)
   *
   */
  public function testEventConfirmationFormPostProcessSetsParticipantStatusToPending() {
    $result = $this->callAPISuccess('Event', 'get', [
      'sequential' => 1,
    ]);

    $event = CRM_Utils_Array::first($result["values"]);

    $contact = $this->createDemoContact();
    $participantParams = [
      'event_id' => $event["id"],
      'contact_id' => $contact["id"],
      'status_id' => 1,
    ];
    $participant = $this->callAPISuccess('Participant', 'create', $participantParams);


    // create a pending contribution
    $transactionId = date("Y-m-d") . mt_rand();
    $contributionParams = [
      'financial_type_id' => "Donation",
      'receive_date' => "2021-01-18 18:16:09",
      'total_amount' => 10,
      'contact_id' => $contact["id"],
      'currency' => "USD",
      'trxn_id' => $transactionId,
      'invoice_id' => $transactionId . "Ref",
      'contribution_status_id' => 2,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);


    // complete the contribution's transaction - this creates a Payment / Financial Transaction entity
    $result = $this->callAPISuccess('Contribution', 'completetransaction', [
      'id' => $contribution["id"],
    ]);

    // link the contribution to the participant
    $result = $this->callAPISuccess('ParticipantPayment', 'create', [
      'participant_id' => $participant["id"],
      'contribution_id' => $contribution["id"],
    ]);

    $formName = 'CRM_Event_Form_Registration_Confirm';
    $form = new stdClass();
    $form->_values = [
      "contributionId" => $contribution["id"],
      "participant" => $participant,
    ];

    btcpay_civicrm_postProcess($formName, $form);
    $updatedParticipant = $this->callAPISuccess('Participant', 'getsingle', [
      'id' => $participant["id"],
    ]);

    $this->assertEquals("Pending (incomplete transaction)", $updatedParticipant["participant_status"]);
    $this->assertEquals(6, $updatedParticipant["participant_status_id"]);
  }

  /**
   * BEFORE RUNNING TEST
   * Ensure there is at least one event in the local installation of CiviCRM
   * (the installation this extension is installed on)
   *
   * This test is just like the previous test but it's for scenarios where
   * there's a main participant who registered other participants + themselves
   *
   */
  public function testEventConfirmationFormPostProcessSetsAllParticipantStatusToPending() {
    $result = $this->callAPISuccess('Event', 'get', [
      'sequential' => 1,
    ]);

    $event = CRM_Utils_Array::first($result["values"]);

    $contact = $this->createDemoContact();
    $mainParticipantParams = [
      'event_id' => $event["id"],
      'contact_id' => $contact["id"],
      'status_id' => 1,
    ];
    $mainParticipant = $this->callAPISuccess('Participant', 'create', $mainParticipantParams);

    $participantIds = [];

    // create 3 other participants who are registered by the main participant
    for ($i = 0; $i < 3; $i++) {
      $contact = $this->createDemoContact();
      $participantParams = [
        'event_id' => $event["id"],
        'contact_id' => $contact["id"],
        'status_id' => 1,
        'registered_by_id' => $mainParticipant["id"],
      ];
      $participant = $this->callAPISuccess('Participant', 'create', $participantParams);
      $participantIds[] = $participant["id"];
    }

    // create a pending contribution
    $transactionId = date("Y-m-d") . mt_rand();
    $contributionParams = [
      'financial_type_id' => "Donation",
      'receive_date' => "2021-01-18 18:16:09",
      'total_amount' => 10,
      'contact_id' => $contact["id"],
      'currency' => "USD",
      'trxn_id' => $transactionId,
      'invoice_id' => $transactionId . "Ref",
      'contribution_status_id' => 2,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);


    // complete the contribution's transaction - this creates a Payment / Financial Transaction entity
    $result = $this->callAPISuccess('Contribution', 'completetransaction', [
      'id' => $contribution["id"],
    ]);

    // link the contribution to the main participant
    $result = $this->callAPISuccess('ParticipantPayment', 'create', [
      'participant_id' => $mainParticipant["id"],
      'contribution_id' => $contribution["id"],
    ]);

    $formName = 'CRM_Event_Form_Registration_Confirm';
    $form = new stdClass();

    // set the participant in this form to any of the other participants because this is what Civi would do
    // before calling the postProcess hook

    $secondRegisteredParticipant = $this->callAPISuccess('Participant', 'getsingle', [
      "id" => $participantIds[0],
    ]);

    $form->_values = [
      "contributionId" => $contribution["id"],
      "participant" => $secondRegisteredParticipant,
    ];

    btcpay_civicrm_postProcess($formName, $form);

    // add the main participant's Id to the $participantIds array
    // and assert that all the participants' status has been updated
    $participantIds[] = $mainParticipant["id"];

    foreach ($participantIds as $participantId) {
      $updatedParticipant = $this->callAPISuccess('Participant', 'getsingle', [
        'id' => $participantId,
      ]);

      $this->assertEquals("Pending (incomplete transaction)", $updatedParticipant["participant_status"]);
      $this->assertEquals(6, $updatedParticipant["participant_status_id"]);
    }

  }

  /**
   * BEFORE RUNNING TEST
   * Ensure there is at least one event in the local installation of CiviCRM
   * (the installation this extension is installed on)
   *
   */
  public function testEventConfirmationFormPostProcessSetsContributionStatusToPending() {
    $result = $this->callAPISuccess('Event', 'get', [
      'sequential' => 1,
    ]);

    $event = CRM_Utils_Array::first($result["values"]);

    $contact = $this->createDemoContact();
    $participantParams = [
      'event_id' => $event["id"],
      'contact_id' => $contact["id"],
      'status_id' => 1,
    ];
    $participant = $this->callAPISuccess('Participant', 'create', $participantParams);


    // create a pending contribution
    $transactionId = date("Y-m-d") . mt_rand();
    $contributionParams = [
      'financial_type_id' => "Donation",
      'receive_date' => "2021-01-18 18:16:09",
      'total_amount' => 10,
      'contact_id' => $contact["id"],
      'currency' => "USD",
      'trxn_id' => $transactionId,
      'invoice_id' => $transactionId . "Ref",
      'contribution_status_id' => 2,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);


    // complete the contribution's transaction - this creates a Payment / Financial Transaction entity
    $result = $this->callAPISuccess('Contribution', 'completetransaction', [
      'id' => $contribution["id"],
    ]);

    // link the contribution to the participant
    $result = $this->callAPISuccess('ParticipantPayment', 'create', [
      'participant_id' => $participant["id"],
      'contribution_id' => $contribution["id"],
    ]);

    $formName = 'CRM_Event_Form_Registration_Confirm';
    $form = new stdClass();
    $form->_values = [
      "contributionId" => $contribution["id"],
      "participant" => $participant,
    ];

    btcpay_civicrm_postProcess($formName, $form);

    // the postProcess ought to delete the original contributon and create a new pending one.

    // attempt to retrieve the original contribution and confirm it's deleted
    $result = $this->callAPISuccess('Contribution', 'get', [
      'sequential' => 1,
      'id' => $contribution["id"],
    ]);

    $this->assertEquals(0, count($result["values"]));

    // retrieve the new contribution using the unique transaction id
    $updatedContribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'trxn_id' => $transactionId,
    ]);

    $this->assertEquals(2, $updatedContribution["contribution_status_id"]);
    $this->assertEquals("Pending", $updatedContribution["contribution_status"]);
  }


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

