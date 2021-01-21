<div class="crm-group credit_card-group">
  <div class="header-dark">
    {ts}Payment Information{/ts}
  </div>
  <div class="display-block">
    <p>You can click the button below (requires Javascript) to copy the Bitcoin Address you'll need to complete this
      transaction.</p>
    <p>You can also use the following details to make payment</p><br/>
    <p>
      {ts}Payment Url{/ts}: <a href="{$btcpayPaymentUrl}" target="_blank"><strong>{$btcpayPaymentUrl}</strong></a><br/>
      {ts}Bitcoin Due{/ts}: <strong>{$btcpayBtcDue}</strong><br/>
      {ts}Pay into this BTC Address{/ts}: <strong>{$btcpayBitcoinAddress}</strong><br/>
      {ts}BTC to {$btcpayCurrency} Rate{/ts}: <strong>{$btcpayRate}</strong><br/>
    </p>
    <p><strong>We will send you an email after confirming your payment</strong></p>
  </div>
  <div class="crm-section crm-btcpay-block">
    <div class="crm-btcpay" id="btcpay-trxnid" style="display: none">{$btcpayTrxnId}</div>
    <a id="btcpay-payment-link" href="javascript:void(0)" onclick="btcpay.showInvoice('{$btcpayTrxnId}')">
      <img src="{$btcpayServerUrl}/img/paybutton/pay.svg" alt="Pay with BTCPay" style="padding: 30px"/>
    </a>
  </div>
</div>

{literal}
  <script type="text/javascript">
    CRM.$(function ($) {
      CRM.$('.crm-btcpay-block').appendTo('div.crm-group.amount_display-group div.display-block');
      btcpay.showInvoice(CRM.$('#btcpay-trxnid').text());
    });
  </script>
{/literal}
