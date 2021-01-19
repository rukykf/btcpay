# BTCPay

Accept Bitcoin payments by integrating CiviCRM with a self-hosted BTCPay Server.

It pops up an invoice (if Javascript is enabled) that shows users a Bitcoin Address where they can send funds to.

The bitcoin address and bitcoin amount is also displayed on the Contribution and Events Registration Thank You Form
pages in case the user has Javascript disabled and the invoice can't pop-up on the screen.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.0+
* CiviCRM v5.32+

## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and install it with the command-line
tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl btcpay@https://github.com/FIXME/btcpay/archive/master.zip
```

## Installation (CLI, Git)
Enable and install the extension on your CiviCRM site.

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and install it
with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/FIXME/btcpay.git
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
    * Don't enter anything for "Pairing token".
    * After creating the payment processor, take note of its ID (available on the list of Payment Processors in CiviCRM's Admin)

4. Create keys for the payment processor. Go to **Support -> Developer -> API Explorer V3** and run the *Btcpay.createkeys* action
with the ID of the payment processor we created earlier as `payment_processor_id`.

5. On your self-hosted Btcpay Server, create a store and from within the store's menu, generate the pairing code for a new access token.
   **Stores -> YOUR-STORE-NAME -> Access Tokens -> Request Pairing**. Leave the public key field blank because we need a pairing code for a server-initiated pairing.
   You should see the pairing code on the screen.

6. In the API Explorer, run the *Btcpay.pair* action with the payment processor ID and the pairing code you just generated as parameters.

7. You should now be able to use this payment processor on any Contribution and Event Registration pages.
