# BTCPay

Payment Processor Extension to accept Bitcoin payments by integrating CiviCRM with a self-hosted BTCPay Server.

It pops up an invoice (if Javascript is enabled) that shows users a Bitcoin Address where they can send funds to.

The bitcoin address and bitcoin amount is also displayed on the Contribution and Events Registration Thank You Form
pages in case the user has Javascript disabled and the invoice can't pop-up on the screen.

After BTCPay Server confirms the payment and sends a notification back to the payment processor, it sends a payment receipt
to the contributor.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.0+
* CiviCRM v5.32+

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Git)
Enable and install the extension on your CiviCRM site.

Sysadmins and developers may clone the [Git](https://gitlab.com/rukkyfsfcontributions/btcpay-civicrm-ext.git) repo for this extension and install it
with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://gitlab.com/rukkyfsfcontributions/btcpay-civicrm-ext.git
cv en btcpay
```

For more instructions on how to install an extension on CiviCRM,
check [https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/#installing-a-new-extension](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/#installing-a-new-extension)


## Usage
Before you can begin processing Bitcoin payments with this extension, ensure you've done the following:
1. Setup your Btcpay Server. If you are looking to test the extension,
set the Btcpay Server up in Testnet mode.

[Learn More on Btcpay Server documentation](https://docs.btcpayserver.org/)

2. Ensure this extension is installed and enabled on your CiviCRM instance.

3.  In your CiviCRM instance, create a new Payment Processor at **Administer -> System Settings -> Payment Processors -> Add Payment Processor**
    * Select **Btcpay** for Payment Processor Type.
    * Payment method is Bitcoin.
    * In the Live details and Test details section of the processor, enter in the URL of your self-hosted BTCPay server in the *Site Url* field.
    * If you want to use the processor in test-mode, you will need to make sure your Btcpay server is setup in Testnet mode. This will allow you receive Testnet Bitcoin payments.
    * Enter a *"Private Key decryption password"* of your choice.
    * Enter any value for *"API Key"* - we don't use this.
    * Don't enter anything for *"Pairing token"*.
    * After creating the payment processor, take note of its ID (available on the list of Payment Processors in CiviCRM's Admin)


4. Create keys for the payment processor. Go to **Support -> Developer -> API Explorer V3** and run the *Btcpay.createkeys* action
with the ID of the payment processor we created earlier as `payment_processor_id`.

5. On your self-hosted Btcpay Server, create a store and from within the store's menu, generate the pairing code for a new access token.
   **Stores -> YOUR-STORE-NAME -> Access Tokens -> Request Pairing**. Leave the public key field blank because we need a pairing code for a server-initiated pairing.
   You should see the pairing code on the screen.

6. In the API Explorer, run the *Btcpay.pair* action with the payment processor ID and the pairing code you just generated as parameters.

7. You should now be able to use this payment processor on any Contribution and Event Registration pages.

### Receiving Membership Dues
This extension does not support recurring and / or auto-renewable memberships. That said, users can still
pay for their CiviMember memberships with Bitcoin using this payment processor extension. Just make sure to schedule a reminder
to these members so they can renew their membership manually before the membership expires.

## Running the tests
There are some e2e tests you can run with a global installation of PHPUnit. Learn more about CiviCRM e2e tests here:
[CiviCRM E2E Test Docs](https://docs.civicrm.org/dev/en/latest/testing/#e2e)

You can run all the tests using
```bash
phpunit // to run all tests
phpunit --filter testEventConfirmationFormPostProcessSetsAllParticipantStatusToPending // to run a specific test
```
The tests currently do not work if you attempt to run them with a composer installed phpunit package. You need a global phpunit installation to get them to work.

The e2e tests all require you to have this extension installed and enabled on a CiviCRM instance.
Each of the tests has its own unique setup requirements. So if you run the tests and find that a test is failing, read through the docs on top of the tests
to see what you need to create / install in the CiviCRM instance to get the tests to work.

## Librejs Support
There is license information for the little Javascript uses in this extension.

That said, this extension works by loading the invoice page from BTCPayServer in an iFrame on the Contribution (or Event) Thank You Page.
The link to this invoice is dynamically generated by BTCPayServer and the most straightforward way
to add license information for the Javascript in this invoice is to edit BTCPayServer's source file.
Specifically, add the appropriate license text (for BTCPayServer, this is the Expat License) to the very top of this file within the BTCPayServer source directory.

**btcpayserver-repository-root/BTCPayServer/Views/Invoice/Checkout.cshtml**

After adding the license script, rebuild BTCPayServer and run it.

For more information about this see:

[Adding License Information to your Javascript](https://www.gnu.org/software/librejs/free-your-javascript.html)

[Building and Running BTCPayServer](https://docs.btcpayserver.org/LocalDevelopment/)

## Troubleshooting Common Issues

### BTCPay Invoice does not pop-up on Thank You page

Ensure that the same-origin policy is disabled on your BTCPay Server.

Also check that your BTCPay store on your BTCPay Server has been setup for the cryptocurrencies you want to support (Bitcoin and Litecoin)
