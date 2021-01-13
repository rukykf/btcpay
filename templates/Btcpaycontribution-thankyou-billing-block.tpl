<div class="crm-group credit_card-group">
  <div class="header-dark">
    {ts}Payment Information{/ts}
  </div>
  <div>
    Something goes here
  </div>
  <div class="crm-section crm-btcpay-block">
    <div class="crm-btcpay" id="btcpay-trxnid" style="display: none">{$btcpayTrxnId}</div>
    <a id="btcpay-payment-link" href="javascript:void(0)" onclick="btcpay.showInvoice('{$btcpayTrxnId}')">
      <img  src="{$btcpayServerUrl}/img/paybutton/pay.svg" alt="Pay with BTCPay" style="padding: 30px" />
    </a>
  </div>
</div>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    CRM.$('.crm-btcpay-block').appendTo('div.crm-group.amount_display-group div.display-block');
    btcpay.showInvoice(CRM.$('#btcpay-trxnid').text());
  });
</script>
{/literal}
